# Site unifié Technic Compo — techniccompo.tn

## Arborescence

```
/                 accueil (vitrine)
/medical/         commande d'imprimés médicaux
/laboratoires/    renvoie vers /medical/ (espace Laboratoires)
/imagerie/        renvoie vers /medical/ (espace Imagerie)
/tarifs/          liste complète des prix, en HTML brut
/societes/        catalogue sociétés + demande de devis hors catalogue
/scandar/         présentation Scandar + demande
/scandar/commander/  bon de commande Scandar (packs, supports QR)
/partenaires/     tarifs partenaires — accès par code (666)
/merci/           page de confirmation après envoi d'un formulaire
.htaccess         HTTPS, redirections, cache
robots.txt        /partenaires/ exclu de Google
sitemap.xml       les quatre pages publiques
```

## Adresse e-mail

Les demandes du site partent vers **hello@techniccompo.tn**, sauf les deux pages Scandar
qui gardent leur propre adresse, **hello@scandar.tn**.

Sept formulaires au total :

| Formulaire | Page | Ce qu'il envoie |
|---|---|---|
| `demande-devis` | accueil | demande générale |
| `commande` | médical | commande de carnets + visuels |
| `commande-societes` | sociétés | bon de commande chiffré |
| `devis-libre` | sociétés | travail hors catalogue à chiffrer |
| `demande-scandar` | scandar | demande d'abonnement Scandar → hello@scandar.tn |
| `commande-scandar` | scandar/commander | bon de commande Scandar chiffré → hello@scandar.tn |
| `devenir-partenaire` | partenaires | candidature partenaire |

Les neuf formulaires d'origine sont aujourd'hui sept (les pages laboratoires et imagerie
renvoient vers la page médicale). Ils partent tous par **`envoi.php`**, à la racine, qui fixe
lui-même le destinataire. Détail dans `POUR-LE-PROGRAMMEUR.md`.

## Espace partenaire

Code actuel : **666**. Il n'est pas écrit en clair dans le fichier, seule son empreinte
SHA-256 y figure (`partenaires/index.html`, constante `EMPREINTE`).

Ce code est un rideau, pas une serrure : la page est déjà chargée derrière l'écran.
La vraie protection est le fichier `partenaires/.htaccess` — quatre lignes à décommenter
par l'hébergeur. Sans identifiant, le serveur ne livre pas la page du tout.

## Mise en ligne

1. Déposer tout le contenu de ce dossier à la racine du site.
2. Vérifier que le certificat SSL est actif.
3. Créer hello@techniccompo.tn (SPF + DKIM), puis envoyer chaque formulaire une fois pour tester.
4. Demander à l'hébergeur d'activer le verrou sur `/partenaires/`.
5. Aucune redirection depuis Netlify : l'adresse Netlify n'a jamais été donnée aux clients.
6. Déclarer le site dans Google Search Console.

## À savoir

L'accueil de cette archive est la version en ligne du 12 septembre 2026, avec ses 18 images
et la galerie Réalisations. Le dossier `images/` doit rester à côté de `index.html`.

Le fichier `_headers` ne sert que sur Netlify. Sur l'hébergement `.tn`, c'est le `.htaccess`
qui gère le cache — les deux peuvent cohabiter sans se gêner.

Les images sont encore celles issues de la planche contact, en résolution limitée. Le cache
est volontairement réglé à 24 heures dans `_headers` : quand les fichiers pleine résolution
arriveront, il suffira de les remplacer sous les mêmes noms.
