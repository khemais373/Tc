# Technic Compo — note pour le développeur

Site statique, HTML/CSS/JS sans build. À déposer tel quel à la racine du domaine.
Aucune base de données, aucun compte utilisateur.

---

## 1. Ce qui est à faire côté serveur

### Adresses e-mail à créer

| Adresse | Reçoit |
|---|---|
| `hello@technic-compo.tn` | accueil, médical, sociétés, partenaires |
| `hello@scandar.tn` | les deux pages Scandar |

Prévoir SPF et DKIM sur les deux domaines. Sans eux, un domaine neuf part en spam
et les demandes se perdent sans que personne s'en aperçoive.

### Les sept formulaires

| `form-name` | Page | Destinataire | Pièces jointes |
|---|---|---|---|
| `demande-devis` | `/` | hello@technic-compo.tn | non |
| `commande` | `/medical/` | hello@technic-compo.tn | **oui** — jusqu'à 6 visuels |
| `commande-societes` | `/societes/` | hello@technic-compo.tn | oui — logos, maquettes |
| `devis-libre` | `/societes/` | hello@technic-compo.tn | oui |
| `commande-laboratoire` | `/laboratoires/` | hello@technic-compo.tn | oui |
| `commande-imagerie` | `/imagerie/` | hello@technic-compo.tn | oui |
| `demande-scandar` | `/scandar/` | hello@scandar.tn | non |
| `commande-scandar` | `/scandar/commander/` | hello@scandar.tn | non |
| `devenir-partenaire` | `/partenaires/` | hello@technic-compo.tn | non |

Chaque formulaire porte un champ caché `destinataire` : c'est une indication,
il ne route rien. C'est le backend qui décide.

### Aujourd'hui : Netlify Forms

Les formulaires sont déclarés en `data-netlify="true"`. Il reste à créer une
notification e-mail **pour chacun des neuf**, dans Forms → Form notifications.
Une seule notification ne couvre pas les autres.

### Après migration sur l'hébergement .tn

Netlify Forms ne fonctionne plus. Deux voies :

**Web3Forms** (aucun serveur à gérer) — créer un compte par adresse, puis :
- `/medical/` : bloc `ENVOI` en haut du script → `MODE: 'web3forms'` + `CLE_WEB3FORMS`
- les autres pages : remplacer l'`action` du formulaire par `https://api.web3forms.com/submit`
  et ajouter un champ caché `access_key`

**PHP** (si l'hébergement le permet) — un `envoi.php` avec PHPMailer en SMTP authentifié,
et l'`action` de chaque formulaire pointée dessus. À privilégier pour le formulaire médical :
il envoie des visuels haute définition, jusqu'à 7 Mo, et les offres gratuites de
services tiers plafonnent souvent plus bas.

Les champs de chaque formulaire sont déclarés dans le HTML — pour le médical, dans un
formulaire caché en bas de page. **Tout champ non déclaré est perdu à la réception.**

### Redirection après envoi

Tous les formulaires redirigent vers `/merci/`. À conserver quelle que soit la solution retenue.

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

`/partenaires/` affiche un écran de code (actuellement `666`, stocké en empreinte
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
- Prévoir les redirections 301 depuis les anciennes adresses Netlify.
- Les grilles de prix sont en dur dans chaque page (constantes en haut de script).
  **Ne jamais les modifier sans accord écrit du gérant.**
- Aucune mention de matériel de production nulle part, y compris dans les attributs `alt`.
