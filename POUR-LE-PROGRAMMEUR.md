# Technic Compo — note pour le développeur

Site statique, HTML/CSS/JS sans build. À déposer tel quel à la racine du domaine.
Aucune base de données, aucun compte utilisateur.

---

## 1. Ce qui est à faire côté serveur

### Adresses e-mail à créer

| Adresse | Reçoit |
|---|---|
| `hello@techniccompo.tn` | accueil, médical, sociétés, partenaires |
| `hello@scandar.tn` | les deux pages Scandar |

Prévoir SPF et DKIM sur les deux domaines. Sans eux, un domaine neuf part en spam
et les demandes se perdent sans que personne s'en aperçoive.

### Les formulaires — déjà branchés sur `envoi.php`

Tous les formulaires du site envoient vers **`/envoi.php`** (à la racine), qui part par la
fonction `mail()` de PHP. Il n'y a **rien à brancher** : il suffit que l'hébergement exécute
PHP et que l'adresse **hello@techniccompo.tn** existe, avec SPF et DKIM sur le domaine.

| `form-name` | Page | Destinataire | Pièces jointes |
|---|---|---|---|
| `demande-devis` | `/` | hello@techniccompo.tn | non |
| `commande` | `/medical/` (médecins, laboratoires, imagerie) | hello@techniccompo.tn | **oui** — visuels |
| `commande-societes` | `/societes/` | hello@techniccompo.tn | oui — logos, maquettes |
| `devis-libre` | `/societes/` | hello@techniccompo.tn | oui |
| `devenir-partenaire` | `/partenaires/` | hello@techniccompo.tn | non |
| `demande-scandar` | `/scandar/` | hello@scandar.tn | non |
| `commande-scandar` | `/scandar/commander/` | hello@scandar.tn | non |

- Le destinataire est fixé **dans `envoi.php`** ; le champ caché `destinataire` des pages est ignoré.
- Tous les champs reçus sont recopiés dans le mail, l'adresse du client est mise en « Répondre à ».
- Pièces jointes : images et PDF, 10 Mo au total. Régler si possible `upload_max_filesize = 10M`
  et `post_max_size = 12M` dans PHP.
- Robots : un champ piège rempli = rien n'est envoyé.
- Formulaire classique → redirection vers `/merci/` ; envoi par JavaScript → réponse JSON `{ok:true}`.

**Si `mail()` est désactivé chez l'hébergeur** : remplacer l'appel `mail()` en fin de fichier par
PHPMailer en SMTP authentifié avec le compte hello@techniccompo.tn. C'est le seul endroit à changer.

**Tester après mise en ligne** : envoyer chaque formulaire une fois et vérifier la réception
(pensez à regarder les indésirables).

Les attributs `data-netlify` restés dans le HTML ne gênent pas hors Netlify.

---

## 2. WhatsApp : le filet de sécurité

Chaque page a un chemin WhatsApp qui fonctionne **sans aucun backend** : le message
est construit en JavaScript et ouvert via `wa.me`, numéro **216 24 678 721**.

| Page | Où |
|---|---|
| `/` | sous le bouton d'envoi, et dans l'estimateur de prix |
| `/medical/` | écran de confirmation, et sur chaque commande de l'historique |
| `/laboratoires/` | sous le formulaire |
| `/imagerie/` | sous le formulaire |
| `/societes/` | sous la barre de total, et sous le formulaire de devis libre |
| `/partenaires/` | bouton d'envoi principal, et écran d'accès partenaire |
| `/scandar/` | sous le formulaire |
| `/scandar/commander/` | bouton WhatsApp du récapitulatif |
| `/merci/` | lien de contact |

**Ce chemin ne doit jamais être retiré ni cassé.** Sur l'accueil, le médical et
Scandar, il se déclenche automatiquement si l'envoi par mail échoue : c'est ce qui
évite qu'une commande soit perdue en silence.

Limite connue : un lien `wa.me` ne peut pas joindre de fichier. Sur la page médicale,
les visuels sont donc enregistrés dans la galerie du téléphone avant l'ouverture de
WhatsApp, et le client les joint lui-même.

---

## 3. Espace partenaire

`/partenaires/` affiche un écran de code (stocké uniquement en empreinte
SHA-256 dans la constante `EMPREINTE`). **C'est un rideau, pas une serrure** : la page
est déjà chargée derrière.

La vraie protection est `partenaires/.htaccess` : quatre lignes à décommenter après
avoir créé le fichier `.htpasswd`. À faire à la mise en ligne — cette page contient
des tarifs qui ne doivent pas être visibles des clients ordinaires.

`/partenaires/` est exclu de `robots.txt` et du sitemap, mais il est désormais **lié depuis le menu
du site** à la demande du gérant. Le verrou serveur devient donc indispensable : sans lui, n'importe
quel visiteur atteint la page et n'a plus que l'écran de code entre lui et les tarifs partenaires.

---

## 4. Divers

- La page `/tarifs/` est volontairement sans JavaScript : ne pas la convertir.
- `.htaccess` racine : HTTPS forcé, redirection www, compression, cache images.
- `_headers` ne sert que sur Netlify ; les deux fichiers peuvent cohabiter.
- Images du site dans `/images/`, celles du bon de commande Scandar dans
  `/scandar/commander/images/`. Ne pas renommer.
- Pas de redirection à prévoir depuis Netlify : l'adresse n'a jamais été donnée aux clients.
- Les grilles de prix sont en dur dans chaque page (constantes en haut de script).
  **Ne jamais les modifier sans accord écrit du gérant.**
- Aucune mention de matériel de production nulle part, y compris dans les attributs `alt`.
