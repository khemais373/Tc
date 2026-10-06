<?php
/* ============================================================
   ENVOI DES FORMULAIRES — techniccompo.tn
   Remplace Netlify Forms sur l'hébergement classique (PHP).
   Tous les formulaires du site arrivent ici, puis partent par mail.

   - Le destinataire est fixé ICI, jamais par le formulaire
     (sinon n'importe qui pourrait envoyer des mails à n'importe qui).
   - Les pages Scandar partent vers hello@scandar.tn, tout le reste
     vers hello@techniccompo.tn.
   - Pièces jointes acceptées : images et PDF, 10 Mo au total.
   - Formulaire classique  -> redirection vers /merci/
     Envoi par JavaScript   -> réponse JSON {ok:true}

   Côté hébergeur : la fonction mail() de PHP doit être active, et
   l'adresse hello@techniccompo.tn doit exister (SPF + DKIM du domaine).
   Si possible : upload_max_filesize = 10M et post_max_size = 12M.
   ============================================================ */

date_default_timezone_set('Africa/Tunis');

const EMAIL_TC      = 'hello@techniccompo.tn';
const EMAIL_SCANDAR = 'hello@scandar.tn';
const EXPEDITEUR    = 'hello@techniccompo.tn';   // doit appartenir au domaine du site
const MAX_FICHIERS  = 10 * 1024 * 1024;

const FORMULAIRES = [
  'demande-devis'      => [EMAIL_TC,      'Demande de devis — site'],
  'commande'           => [EMAIL_TC,      'Commande imprimés médicaux'],
  'commande-societes'  => [EMAIL_TC,      'Commande sociétés'],
  'devis-libre'        => [EMAIL_TC,      'Devis hors catalogue'],
  'devenir-partenaire' => [EMAIL_TC,      'Candidature partenaire'],
  'demande-scandar'    => [EMAIL_SCANDAR, 'Demande Scandar'],
  'commande-scandar'   => [EMAIL_SCANDAR, 'Commande Scandar'],
];

/* Champs techniques à ne pas recopier dans le mail */
const IGNORES = ['form-name', 'destinataire', 'bot-field', 'bot-field-devis',
                 'bot-field-scandar', 'bot-field-sc', 'access_key', 'subject',
                 'from_name', 'replyto', 'redirect'];

function veut_json() {
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  return strpos($accept, 'text/html') === false;   // fetch() n'annonce pas text/html
}
function reponse($ok, $code = 200) {
  if (veut_json()) {
    http_response_code($ok ? 200 : $code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok]);
  } else {
    header('Location: ' . ($ok ? '/merci/' : '/?erreur=envoi'), true, 303);
  }
  exit;
}
function propre($v) {                                   // pas de retour à la ligne dans les en-têtes
  return trim(str_replace(["\r", "\n"], ' ', (string)$v));
}
function entete_utf8($texte) {
  return '=?UTF-8?B?' . base64_encode($texte) . '?=';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reponse(false, 405);

$nom_form = $_POST['form-name'] ?? '';
if (!isset(FORMULAIRES[$nom_form])) reponse(false, 400);
[$destinataire, $objet] = FORMULAIRES[$nom_form];

/* Robots : un champ piège rempli = on fait semblant d'avoir envoyé */
foreach (['bot-field', 'bot-field-devis', 'bot-field-scandar', 'bot-field-sc'] as $piege) {
  if (!empty($_POST[$piege])) reponse(true);
}

/* Corps du mail : tous les champs, dans l'ordre du formulaire */
$lignes = [];
foreach ($_POST as $cle => $valeur) {
  if (in_array($cle, IGNORES, true)) continue;
  if (is_array($valeur)) $valeur = implode(', ', $valeur);
  $valeur = trim((string)$valeur);
  if ($valeur === '') continue;
  $libelle = ucfirst(str_replace(['_', '-'], ' ', $cle));
  $lignes[] = (strpos($valeur, "\n") !== false)
    ? "$libelle :\n$valeur\n"
    : "$libelle : $valeur";
}
$ref = propre($_POST['reference'] ?? $_POST['etablissement'] ?? $_POST['nom'] ?? $_POST['client'] ?? '');
$sujet = $objet . ($ref !== '' ? ' — ' . mb_substr($ref, 0, 80) : '');
$texte = $sujet . "\n" . str_repeat('-', 40) . "\n\n" . implode("\n", $lignes)
       . "\n\n--\nEnvoyé depuis techniccompo.tn le " . date('d/m/Y à H:i') . "\n";

/* Pièces jointes : images et PDF uniquement */
$fichiers = [];
$total = 0;
$types_ok = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
foreach ($_FILES as $champ) {
  $noms = (array)$champ['name']; $tmps = (array)$champ['tmp_name'];
  $errs = (array)$champ['error']; $tailles = (array)$champ['size'];
  foreach ($noms as $i => $nom) {
    if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    if (!is_uploaded_file($tmps[$i])) continue;
    $type = function_exists('mime_content_type') ? mime_content_type($tmps[$i]) : 'application/octet-stream';
    if (!in_array($type, $types_ok, true)) continue;
    $total += $tailles[$i];
    if ($total > MAX_FICHIERS) break 2;
    $fichiers[] = ['nom' => preg_replace('/[^\w.\- ]+/u', '_', basename($nom)) ?: 'fichier',
                   'type' => $type, 'data' => file_get_contents($tmps[$i])];
  }
}
if ($total > MAX_FICHIERS) $texte .= "\n(Certains fichiers dépassaient 10 Mo et n'ont pas été joints.)\n";

/* En-têtes */
$entetes  = 'From: ' . entete_utf8('Site Technic Compo') . ' <' . EXPEDITEUR . ">\r\n";
$email_client = filter_var($_POST['email'] ?? $_POST['replyto'] ?? '', FILTER_VALIDATE_EMAIL);
if ($email_client) $entetes .= 'Reply-To: ' . propre($email_client) . "\r\n";
$entetes .= "MIME-Version: 1.0\r\n";

if ($fichiers) {
  $frontiere = 'tc_' . bin2hex(random_bytes(12));
  $entetes .= "Content-Type: multipart/mixed; boundary=\"$frontiere\"\r\n";
  $corps  = "--$frontiere\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($texte)) . "\r\n";
  foreach ($fichiers as $f) {
    $corps .= "--$frontiere\r\nContent-Type: {$f['type']}; name=\"" . entete_utf8($f['nom']) . "\"\r\n"
            . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" . entete_utf8($f['nom']) . "\"\r\n\r\n"
            . chunk_split(base64_encode($f['data'])) . "\r\n";
  }
  $corps .= "--$frontiere--";
} else {
  $entetes .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
  $corps = chunk_split(base64_encode($texte));
}

$ok = @mail($destinataire, entete_utf8($sujet), $corps, $entetes, '-f' . EXPEDITEUR);
reponse($ok, 500);
