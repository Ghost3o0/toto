/**
 * Mystate Chatbot - Assistant d'aide intégré
 * Réponses prédéfinies couvrant TOUTES les actions de l'application
 */

const chatbotResponses = [
    // ===================== SALUTATIONS =====================
    {
        keywords: ['bonjour', 'salut', 'hello', 'hey', 'bonsoir', 'coucou'],
        response: "Bonjour ! Je suis l'assistant Mystate. Comment puis-je vous aider ? Vous pouvez me poser des questions sur n'importe quelle fonctionnalité de l'application."
    },
    {
        keywords: ['merci', 'super', 'parfait', 'ok', 'compris', 'genial'],
        response: "Avec plaisir ! N'hésitez pas si vous avez d'autres questions."
    },

    // ===================== CONNEXION / INSCRIPTION =====================
    {
        keywords: ['connexion', 'connecter', 'login', 'se connecter', 'identifiant'],
        response: "Pour vous connecter :\n1. Entrez votre nom d'utilisateur\n2. Entrez votre mot de passe\n3. Cliquez sur \"Se connecter\"\n\nSi vous n'avez pas de compte, cliquez sur \"Créer un compte\" en bas de la page."
    },
    {
        keywords: ['inscription', 'inscrire', 'register', 'créer compte', 'nouveau compte'],
        response: "Pour créer un compte :\n1. Cliquez sur \"Créer un compte\" depuis la page de connexion\n2. Remplissez :\n   - Nom d'utilisateur (min. 3 caractères, lettres/chiffres/_)\n   - Email (optionnel)\n   - Mot de passe (min. 6 caractères)\n   - Confirmation du mot de passe\n3. Cliquez sur \"Créer mon compte\""
    },
    {
        keywords: ['mot de passe', 'password', 'mdp'],
        response: "Le mot de passe doit contenir au minimum 6 caractères. Lors de l'inscription, vous devez le saisir deux fois pour confirmation.\n\nSi vous avez oublié votre mot de passe, contactez un administrateur."
    },
    {
        keywords: ['déconnexion', 'déconnecter', 'logout', 'quitter', 'fermer session'],
        response: "Pour vous déconnecter :\n1. Cliquez sur \"Déconnexion\" dans le menu de navigation\n2. Confirmez dans la popup de confirmation\n\nVotre session sera fermée et vous serez redirigé vers la page de connexion."
    },

    // ===================== NAVIGATION =====================
    {
        keywords: ['tableau de bord', 'dashboard', 'accueil', 'page principale'],
        response: "Le Tableau de bord affiche un résumé complet :\n\n- 4 stats principales : stock total, références, valeur du stock, alertes stock bas\n- Stats mensuelles : ventes et factures du mois\n- Rapport du jour : ventes, unités sorties, chiffre d'affaires (avec comparaison vs hier)\n- Tableau des ventes du jour\n- Alertes stock bas avec bouton Réappro\n- Derniers mouvements de stock\n- Top 5 des ventes\n\nCliquez sur \"Tableau de bord\" dans le menu."
    },
    {
        keywords: ['menu', 'navigation', 'navbar', 'barre de navigation'],
        response: "La barre de navigation en haut contient :\n- Tableau de bord : vue d'ensemble de votre activité\n- Téléphones : liste et gestion de vos produits\n- Stock : historique des mouvements (entrées/sorties)\n- Ventes : factures et historique des ventes\n- Partenaires : gestion du partage de stock\n- IMEI : recherche globale par numéro IMEI\n- Soleil/Lune : basculer mode clair/sombre\n- Déconnexion : fermer la session\n\nSur mobile, cliquez sur le bouton hamburger (≡) pour ouvrir le menu."
    },

    // ===================== TÉLÉPHONES - LISTE =====================
    {
        keywords: ['téléphone', 'produit', 'liste téléphone', 'inventaire', 'catalogue'],
        response: "La page Téléphones affiche tous vos produits avec :\n\n- Colonnes : Marque, Modèle, Prix, Quantité, Stock min, Statut, Actions\n- Statuts : OK (vert), Attention (orange), Stock bas (rouge)\n- Recherche par modèle, description ou code-barres\n- Filtre par marque (dropdown)\n- Filtre par statut stock (tout/stock bas)\n- Bouton Scanner pour recherche par code-barres\n- Export CSV pour télécharger toute la liste\n- Mode Compact pour réduire le tableau\n- Pagination en bas de page\n\nActions par téléphone : Stock, Modifier, Supprimer"
    },
    {
        keywords: ['statut stock', 'badge', 'couleur stock', 'ok attention'],
        response: "Les statuts de stock dans la liste Téléphones :\n\n- Badge vert \"OK\" : quantité > 2x le stock minimum\n- Badge orange \"Attention\" : quantité entre 1x et 2x le stock minimum\n- Badge rouge \"Stock bas\" : quantité ≤ stock minimum\n\nLe stock minimum est défini pour chaque téléphone lors de l'ajout ou la modification."
    },

    // ===================== TÉLÉPHONES - AJOUTER =====================
    {
        keywords: ['ajouter téléphone', 'nouveau téléphone', 'créer téléphone', 'ajout produit'],
        response: "Pour ajouter un téléphone :\n1. Allez dans Téléphones\n2. Cliquez sur \"+ Ajouter un téléphone\"\n3. Remplissez le formulaire :\n   - Marque (dropdown, optionnel)\n   - Modèle (obligatoire)\n   - Description (optionnel)\n   - Prix (obligatoire)\n   - Quantité initiale (optionnel)\n   - Stock minimum (optionnel)\n4. Si quantité > 0, saisissez les IMEI de chaque unité\n5. Cliquez sur \"Ajouter le téléphone\"\n\nBoutons : Annuler, Retour à la liste"
    },
    {
        keywords: ['marque', 'brand', 'samsung', 'iphone', 'apple'],
        response: "La marque est sélectionnable dans un dropdown lors de l'ajout ou la modification d'un téléphone.\n\nLes marques disponibles sont celles enregistrées dans le système. Sélectionnez la marque correspondante ou laissez vide si non applicable."
    },
    {
        keywords: ['prix', 'tarif', 'cout', 'price', 'montant produit'],
        response: "Le prix est saisi lors de l'ajout ou la modification d'un téléphone :\n- Champ obligatoire, format décimal\n- Affiché en Ariary (Ar) dans toute l'application\n- Auto-formaté à 2 décimales quand vous quittez le champ\n\nSur la page de modification, l'historique des prix est visible en bas de page (ancien prix, nouveau prix, variation en %, date, utilisateur)."
    },
    {
        keywords: ['quantité initiale', 'quantite', 'combien', 'nombre unité'],
        response: "La quantité initiale est définie à l'ajout d'un téléphone :\n- Si > 0 : des champs IMEI apparaissent pour chaque unité\n- Chaque IMEI peut être saisi manuellement ou scanné\n- La quantité est ensuite gérée via les mouvements de stock (entrées/sorties)\n\nLa quantité actuelle est affichée dans la liste et ne peut pas être modifiée directement (utilisez l'ajustement de stock)."
    },
    {
        keywords: ['stock minimum', 'min stock', 'seuil', 'alerte quantité'],
        response: "Le stock minimum est un seuil d'alerte :\n- Défini lors de l'ajout ou modification d'un téléphone\n- Quand la quantité ≤ stock minimum → badge rouge \"Stock bas\"\n- Apparaît dans les alertes du Tableau de bord\n- Bouton \"Réappro\" pour réapprovisionner rapidement\n\nExemple : stock minimum = 5, quantité = 3 → alerte stock bas"
    },

    // ===================== TÉLÉPHONES - MODIFIER =====================
    {
        keywords: ['modifier téléphone', 'éditer', 'edit', 'changer téléphone', 'mettre à jour'],
        response: "Pour modifier un téléphone :\n1. Dans la liste Téléphones, cliquez sur \"Modifier\"\n2. Modifiez les champs souhaités :\n   - Marque, Modèle, Description\n   - Prix (le changement est historisé)\n   - Stock minimum\n3. Cliquez sur \"Enregistrer les modifications\"\n\nLa page affiche aussi :\n- Quantité actuelle (lecture seule)\n- Liste des IMEI enregistrés avec leur statut\n- Historique des prix (ancien/nouveau prix, variation, date)\n\nBoutons : Gérer le stock, Annuler, Retour à la liste"
    },
    {
        keywords: ['historique prix', 'variation prix', 'changement prix', 'ancien prix'],
        response: "L'historique des prix est visible sur la page de modification d'un téléphone :\n\n- Tableau en bas de page\n- Colonnes : Date, Ancien prix, Nouveau prix, Variation (%), Modifié par\n- Chaque modification de prix est automatiquement enregistrée\n- Permet de suivre l'évolution des tarifs dans le temps"
    },
    {
        keywords: ['liste imei', 'imei enregistré', 'imei téléphone', 'voir imei'],
        response: "Les IMEI enregistrés sont visibles sur la page de modification d'un téléphone :\n\n- Tableau avec : code IMEI, Statut (En stock / Vendu), Date d'ajout\n- Badge vert \"En stock\" : l'unité est disponible\n- Badge rouge \"Vendu\" : l'unité a été vendue\n\nPour ajouter de nouveaux IMEI, faites un mouvement d'entrée de stock."
    },

    // ===================== TÉLÉPHONES - SUPPRIMER =====================
    {
        keywords: ['supprimer téléphone', 'effacer', 'delete', 'enlever produit', 'retirer'],
        response: "Pour supprimer un téléphone :\n1. Dans la liste Téléphones\n2. Cliquez sur \"Supprimer\" (bouton rouge)\n3. Une popup de confirmation apparaît\n4. Confirmez pour supprimer définitivement\n\nAttention : cette action est irréversible ! Le téléphone et ses données associées seront supprimés."
    },

    // ===================== STOCK - AJUSTEMENT =====================
    {
        keywords: ['ajuster stock', 'ajustement', 'mouvement stock', 'entrée stock', 'sortie stock', 'réapprovisionner'],
        response: "Pour ajuster le stock :\n1. Cliquez sur \"Stock\" à côté d'un téléphone, ou \"+ Nouveau mouvement\" dans la page Stock\n2. Sélectionnez le téléphone (dropdown avec stock actuel affiché)\n3. Choisissez le type :\n   - Entrée (+) : saisissez quantité + IMEI de chaque unité\n   - Sortie (-) : cochez les IMEI à sortir du stock\n4. Ajoutez un commentaire/raison (optionnel)\n5. Cliquez sur \"Enregistrer le mouvement\"\n\nBouton \"Voir l'historique\" pour consulter les mouvements passés."
    },
    {
        keywords: ['entrée', 'réception', 'approvisionner', 'réappro', 'ajouter stock'],
        response: "Pour une entrée de stock (réapprovisionnement) :\n1. Page Stock → Nouveau mouvement\n2. Sélectionnez le téléphone\n3. Type : \"Entrée (+)\"\n4. Indiquez la quantité\n5. Saisissez ou scannez l'IMEI de chaque unité entrante\n6. Ajoutez une raison (ex: \"Réception fournisseur\")\n7. Enregistrez\n\nLe stock est immédiatement mis à jour."
    },
    {
        keywords: ['sortie', 'retrait', 'sortir stock', 'déduire'],
        response: "Pour une sortie de stock :\n1. Page Stock → Nouveau mouvement\n2. Sélectionnez le téléphone\n3. Type : \"Sortie (-)\"\n4. Cochez les IMEI des unités à sortir (cases à cocher)\n5. La quantité est calculée automatiquement\n6. Ajoutez une raison (ex: \"Vente directe\", \"Défectueux\")\n7. Enregistrez\n\nNote : les sorties liées aux ventes sont automatiques lors de la création d'une facture."
    },
    {
        keywords: ['raison', 'commentaire', 'motif', 'reason'],
        response: "Le champ Raison/Commentaire lors d'un ajustement de stock :\n- Champ optionnel mais recommandé\n- Permet de tracer pourquoi un mouvement a été fait\n- Exemples : \"Réception fournisseur\", \"Défectueux\", \"Correction inventaire\"\n- Visible dans l'historique des mouvements"
    },

    // ===================== STOCK - HISTORIQUE MOUVEMENTS =====================
    {
        keywords: ['historique mouvement', 'mouvement', 'stock page', 'mouvements'],
        response: "La page Mouvements de stock affiche l'historique complet :\n\n- Colonnes : Date, Téléphone, Type (Entrée/Sortie), Quantité (+/-), Raison, Utilisateur\n- Badge vert \"Entrée\" / Badge rouge \"Sortie\"\n\nFiltres disponibles :\n- Recherche par modèle ou raison\n- Filtre par téléphone (dropdown)\n- Filtre par type (Entrées/Sorties)\n- Plage de dates (début/fin)\n- Export CSV\n- Mode Compact\n- Pagination (20 par page)"
    },
    {
        keywords: ['stock bas', 'alerte', 'minimum', 'rupture', 'alerte stock'],
        response: "Les alertes stock bas :\n\n- Apparaissent quand quantité ≤ stock minimum défini\n- Badge rouge \"Stock bas\" dans la liste Téléphones\n- Section dédiée sur le Tableau de bord\n- Bouton \"Réappro\" pour aller directement à l'ajustement de stock\n- Lien \"Voir tout\" pour voir tous les produits en stock bas\n\nPour résoudre : cliquez \"Réappro\" et faites une entrée de stock."
    },

    // ===================== VENTES - CRÉER =====================
    {
        keywords: ['créer vente', 'nouvelle vente', 'vendre', 'faire vente', 'facturer'],
        response: "Pour créer une vente :\n1. Allez dans Ventes → \"+ Nouvelle vente\"\n2. Remplissez les infos client :\n   - Nom du client (obligatoire)\n   - Téléphone du client (optionnel)\n   - Adresse du client (optionnel)\n3. Ajoutez des lignes de produit :\n   - Sélectionnez un téléphone (stock disponible affiché)\n   - Modifiez le prix unitaire si besoin\n   - Cochez les IMEI des unités vendues\n   - La quantité et le total de ligne se calculent automatiquement\n4. Cliquez \"+ Ajouter une ligne\" pour d'autres produits\n5. Ajoutez des notes (optionnel)\n6. Cliquez \"Enregistrer la vente\"\n\nUne facture avec un numéro unique (FAC-YYYY-NNNNNN) est générée automatiquement."
    },
    {
        keywords: ['ligne', 'ajouter ligne', 'produit vente', 'article'],
        response: "Les lignes de vente dans la création de facture :\n\n- Cliquez \"+ Ajouter une ligne\" pour ajouter un produit\n- Pour chaque ligne :\n  - Sélectionnez le téléphone (dropdown avec stock dispo)\n  - Le prix unitaire est pré-rempli (modifiable)\n  - Cochez les IMEI des unités à vendre\n  - La quantité = nombre d'IMEI cochés\n  - Total ligne = quantité × prix unitaire\n- Bouton \"X\" pour supprimer une ligne\n- Le total général se met à jour automatiquement"
    },
    {
        keywords: ['client', 'nom client', 'info client', 'acheteur'],
        response: "Les informations client lors d'une vente :\n\n- Nom du client : obligatoire, affiché sur la facture\n- Téléphone du client : optionnel, pour le recontacter\n- Adresse du client : optionnel, affichée sur la facture\n\nCes infos sont enregistrées avec la facture et visibles dans le détail et l'impression."
    },
    {
        keywords: ['note', 'notes', 'remarque', 'observation'],
        response: "Les notes sur une facture :\n- Champ optionnel en bas du formulaire de vente\n- Permet d'ajouter un commentaire ou une remarque\n- Affiché dans le détail de la facture\n- Affiché sur la version imprimée\n\nExemple : \"Garantie 6 mois\", \"Livraison prévue le 20/02\""
    },
    {
        keywords: ['numéro facture', 'numero facture', 'fac-', 'référence'],
        response: "Les numéros de facture sont générés automatiquement :\n- Format : FAC-YYYY-NNNNNN (ex: FAC-2026-000042)\n- YYYY = année en cours\n- NNNNNN = numéro séquentiel auto-incrémenté\n- Unique et non modifiable\n- Affiché dans la liste, le détail et l'impression"
    },

    // ===================== VENTES - LISTE =====================
    {
        keywords: ['vente', 'facture', 'historique vente', 'liste vente'],
        response: "La page Ventes affiche l'historique des factures :\n\n- Colonnes : Date, N° Facture, Client, Articles, Total, Statut, Actions\n- Statuts : Terminée (vert) ou Annulée (rouge)\n\nFiltres :\n- Recherche par n° facture ou nom client\n- Plage de dates (début/fin)\n- Filtre par statut (Terminée/Annulée)\n- Export CSV, Mode Compact\n- Pagination (15 par page)\n\nActions : Voir, Imprimer, Annuler"
    },

    // ===================== VENTES - VOIR DÉTAIL =====================
    {
        keywords: ['voir facture', 'détail facture', 'consulter facture', 'détail vente'],
        response: "Le détail d'une facture affiche :\n\n- Infos facture : numéro, date, statut, vendeur\n- Infos client : nom, téléphone, adresse\n- Lignes de la facture : produit, quantité, prix unitaire, total\n- IMEI associés à chaque ligne\n- Total général\n- Notes (si présentes)\n\nBoutons : Imprimer, Annuler (si terminée), Retour à la liste"
    },

    // ===================== VENTES - IMPRIMER =====================
    {
        keywords: ['imprimer', 'impression', 'print', 'pdf', 'papier'],
        response: "Pour imprimer une facture :\n\n1. Depuis la liste des Ventes → bouton \"Imprimer\"\n   OU depuis le détail → bouton \"Imprimer\"\n2. La facture s'ouvre dans un nouvel onglet avec mise en page optimisée\n3. Contenu : en-tête Mystate, infos facture/client, lignes produits avec IMEI, total, notes\n4. Cliquez \"Imprimer\" en haut de la page ou Ctrl+P\n5. Vous pouvez aussi \"Enregistrer en PDF\" depuis la boîte d'impression\n\nLe bouton et les éléments de navigation sont masqués automatiquement à l'impression."
    },

    // ===================== VENTES - ANNULER =====================
    {
        keywords: ['annuler facture', 'annulation', 'cancel', 'annuler vente'],
        response: "Pour annuler une facture :\n1. Depuis la liste des Ventes OU le détail d'une facture\n2. Cliquez sur \"Annuler\" (bouton rouge)\n3. Confirmez dans la popup de confirmation\n\nEffets de l'annulation :\n- Le statut passe de \"Terminée\" à \"Annulée\"\n- Le stock des produits est automatiquement restauré\n- Les IMEI redeviennent disponibles\n- La facture reste visible dans l'historique (badge rouge)\n\nNote : seules les factures \"Terminées\" peuvent être annulées."
    },

    // ===================== IMEI =====================
    {
        keywords: ['imei', 'recherche imei', 'numéro série', 'tracer', 'chercher imei'],
        response: "La recherche IMEI (menu → IMEI) permet de tracer un téléphone :\n\n1. Saisissez le numéro IMEI (complet ou partiel)\n   OU utilisez le bouton Scanner\n2. Cliquez \"Rechercher\"\n3. Résultats affichés :\n   - IMEI, Téléphone (marque + modèle), Prix\n   - Statut : \"En stock\" (vert) ou \"Vendu\" (rouge)\n   - Si vendu : nom du client, n° de facture, date de vente\n\nPermet de retrouver rapidement l'historique d'une unité spécifique."
    },
    {
        keywords: ['scanner', 'code-barres', 'barcode', 'scan', 'caméra', 'camera'],
        response: "Le bouton Scanner utilise la caméra de votre appareil :\n\n1. Cliquez sur l'icône code-barres\n2. Autorisez l'accès à la caméra\n3. Pointez vers le code-barres ou IMEI\n4. Le code est détecté et rempli automatiquement\n5. Vous pouvez aussi saisir le code manuellement en bas\n\nDisponible sur :\n- Liste Téléphones (recherche)\n- Recherche IMEI\n- Ajout de téléphone (saisie IMEI)\n- Ajustement de stock (saisie IMEI)\n\nFormats supportés : EAN-13, EAN-8, Code-128, Code-39, QR Code"
    },
    {
        keywords: ['valider imei', 'vérifier imei', 'imei valide', 'luhn', '15 chiffres'],
        response: "Validation des IMEI dans Mystate :\n- Doit contenir exactement 15 chiffres\n- Vérifié par l'algorithme de Luhn (checksum)\n- Un IMEI invalide sera rejeté lors de la saisie\n\nL'IMEI est le numéro unique d'identification d'un téléphone mobile, généralement inscrit sous la batterie ou accessible via *#06#."
    },

    // ===================== PARTENAIRES =====================
    {
        keywords: ['partenaire', 'partage', 'collaboration', 'partenariat'],
        response: "La section Partenaires permet le partage de stock entre utilisateurs :\n\n- Partenaires actifs : liste avec date et bouton Supprimer\n- Demandes reçues : Accepter ou Refuser\n- Demandes envoyées : statut \"En attente\"\n\nUn partenaire peut voir et vendre votre stock (et inversement).\nToutes les données partagées apparaissent dans vos tableaux de bord et listes."
    },
    {
        keywords: ['inviter', 'invitation', 'ajouter partenaire', 'envoyer invitation'],
        response: "Pour inviter un partenaire :\n1. Allez dans Partenaires\n2. Cliquez sur \"+ Inviter un partenaire\"\n3. Saisissez le nom d'utilisateur exact du partenaire\n4. Cliquez \"Envoyer l'invitation\"\n\nLe partenaire recevra la demande dans sa page Partenaires → section \"Demandes reçues\" et pourra l'accepter ou la refuser."
    },
    {
        keywords: ['accepter', 'refuser', 'demande partenariat', 'requête'],
        response: "Quand vous recevez une demande de partenariat :\n- Elle apparaît dans Partenaires → \"Demandes reçues\"\n- Bouton vert \"Accepter\" : le partenariat est activé, vous partagez vos stocks\n- Bouton rouge \"Refuser\" : la demande est supprimée\n\nUne fois accepté, le partenaire apparaît dans vos partenaires actifs."
    },
    {
        keywords: ['supprimer partenaire', 'retirer partenaire', 'fin partenariat'],
        response: "Pour supprimer un partenariat :\n1. Allez dans Partenaires\n2. Dans la liste des partenaires actifs\n3. Cliquez sur \"Supprimer\" à côté du partenaire\n4. Confirmez dans la popup\n\nLe partage de stock est immédiatement arrêté. Vous ne verrez plus le stock du partenaire et inversement."
    },

    // ===================== FILTRES ET RECHERCHE =====================
    {
        keywords: ['filtre', 'filtrer', 'recherche', 'chercher', 'trouver'],
        response: "Filtres disponibles par page :\n\nTéléphones :\n- Recherche texte (modèle, code-barres, IMEI)\n- Filtre par marque\n- Filtre stock (tout/stock bas)\n- Scanner code-barres\n\nVentes :\n- Recherche (n° facture, client)\n- Plage de dates (début/fin)\n- Statut (Terminée/Annulée)\n\nMouvements :\n- Recherche (modèle, raison)\n- Filtre par téléphone\n- Type (Entrées/Sorties)\n- Plage de dates\n\nBouton \"Filtrer\" = appliquer, \"Réinitialiser\" = tout effacer."
    },
    {
        keywords: ['date', 'plage date', 'période', 'date début', 'date fin'],
        response: "Les filtres par plage de dates :\n- Champ \"Date début\" : résultats à partir de cette date\n- Champ \"Date fin\" : résultats jusqu'à cette date\n- Vous pouvez remplir un seul des deux champs\n- Format : sélecteur de date natif du navigateur\n\nDisponible sur : Ventes et Mouvements de stock.\nL'export CSV respecte les filtres appliqués."
    },
    {
        keywords: ['réinitialiser', 'effacer filtre', 'reset', 'tout afficher'],
        response: "Le bouton \"Réinitialiser\" :\n- Efface tous les filtres appliqués\n- Remet la recherche à vide\n- Affiche tous les résultats sans filtre\n- Revient à la première page\n\nDisponible sur toutes les pages listes (Téléphones, Ventes, Mouvements)."
    },
    {
        keywords: ['pagination', 'page suivante', 'page précédente', 'naviguer'],
        response: "La pagination en bas de chaque liste :\n- Précédent / Suivant pour naviguer\n- Numéros de pages cliquables\n- La page courante est en bleu\n\nNombre d'éléments par page :\n- Téléphones : 10 par page\n- Ventes : 15 par page\n- Mouvements : 20 par page\n\nLes filtres sont conservés lors de la navigation entre pages."
    },

    // ===================== EXPORT CSV =====================
    {
        keywords: ['csv', 'export', 'exporter', 'télécharger', 'excel', 'tableur'],
        response: "Le bouton CSV (icône téléchargement) exporte les données :\n\n1. Appliquez vos filtres si besoin\n2. Cliquez sur le bouton \"CSV\"\n3. Un fichier .csv est téléchargé\n\nContenu exporté par page :\n- Téléphones : Marque, Modèle, Prix, Quantité, Stock min, Statut\n- Ventes : Date, N° Facture, Client, Articles, Total, Statut\n- Mouvements : Date, Téléphone, Type, Quantité, Raison, Utilisateur\n\nLe fichier respecte les filtres actifs, s'ouvre dans Excel/LibreOffice (séparateur ;)."
    },

    // ===================== AFFICHAGE =====================
    {
        keywords: ['compact', 'tableau compact', 'réduire tableau', 'petit tableau'],
        response: "Le bouton \"Compact\" en haut des listes :\n- Réduit l'espacement et la taille du texte dans les tableaux\n- Permet d'afficher plus de lignes à l'écran\n- Le bouton devient bleu quand activé\n- Cliquez à nouveau pour revenir au mode normal\n- Votre préférence est mémorisée (localStorage)\n\nDisponible sur : Téléphones, Ventes, Mouvements."
    },
    {
        keywords: ['masquer', 'cacher', 'oeil', 'valeur', 'montant', 'afficher valeur', 'confidentialité'],
        response: "Le bouton oeil \"Masquer/Afficher\" sur le Tableau de bord :\n\n- Masque toutes les valeurs monétaires (en Ar)\n- Valeurs remplacées par des points (••••••)\n- Concerne : valeur du stock, ventes du mois, chiffre du jour\n- Cliquez à nouveau pour ré-afficher\n- Préférence mémorisée entre les visites\n\nUtile pour la confidentialité quand quelqu'un regarde votre écran."
    },
    {
        keywords: ['sombre', 'dark', 'thème', 'nuit', 'clair', 'mode sombre', 'mode clair'],
        response: "Pour basculer entre mode clair et sombre :\n- Cliquez sur l'icône soleil (☀) ou lune (🌙) dans le menu\n- Mode sombre : fond foncé, reposant pour les yeux la nuit\n- Mode clair : fond blanc classique\n- Se détecte automatiquement selon les préférences de votre système\n- Votre choix est sauvegardé automatiquement"
    },
    {
        keywords: ['comparaison', 'hier', 'pourcentage', 'flèche', 'vs hier', 'progression'],
        response: "Les indicateurs de comparaison vs hier sur le Tableau de bord :\n\nSous chaque stat du rapport du jour :\n- ↑ +X% vs hier (vert) : hausse par rapport à hier\n- ↓ -X% vs hier (rouge) : baisse par rapport à hier\n- = identique (gris) : même valeur qu'hier\n- ↑ nouveau (vert) : activité aujourd'hui mais pas hier\n\nAppliqué sur : nombre de ventes, unités sorties, chiffre du jour."
    },

    // ===================== RAPPORT QUOTIDIEN =====================
    {
        keywords: ['rapport', 'quotidien', 'jour', 'aujourd\'hui', 'daily'],
        response: "Le rapport du jour sur le Tableau de bord :\n\n3 cartes statistiques :\n- Ventes aujourd'hui (nombre) + comparaison vs hier\n- Unités sorties (nombre) + comparaison vs hier\n- Chiffre du jour (Ar) + comparaison vs hier\n\nTableau des ventes du jour :\n- Heure, N° facture, Client, Articles, Montant\n- Affiché uniquement si des ventes ont eu lieu"
    },
    {
        keywords: ['top vente', 'meilleure vente', 'plus vendu', 'top 5'],
        response: "Le Top 5 des ventes sur le Tableau de bord :\n- Affiche les 5 téléphones les plus vendus (toutes périodes)\n- Classement par nombre d'unités sorties\n- Colonnes : rang, produit (marque + modèle), unités vendues"
    },
    {
        keywords: ['derniers mouvements', 'mouvements récents', 'récent'],
        response: "La section \"Derniers mouvements\" sur le Tableau de bord :\n- Affiche les 5 mouvements de stock les plus récents\n- Colonnes : Date/heure, Produit, Mouvement (+/- avec badge couleur)\n- Lien \"Voir tout\" pour accéder à l'historique complet"
    },
    {
        keywords: ['stat mensuel', 'ventes mois', 'factures mois', 'mensuel'],
        response: "Les statistiques mensuelles sur le Tableau de bord :\n- Ventes ce mois : total des montants des factures complétées du mois\n- Factures ce mois : nombre de factures complétées du mois\n\nLes valeurs monétaires peuvent être masquées avec le bouton oeil."
    },

    // ===================== AIDE GÉNÉRALE =====================
    {
        keywords: ['aide', 'help', 'comment', 'quoi faire', 'fonctionnalité', 'c\'est quoi'],
        response: "Je peux vous aider sur toutes les actions de Mystate :\n\n- Connexion / Inscription / Déconnexion\n- Téléphones : ajouter, modifier, supprimer, IMEI\n- Stock : entrée, sortie, ajustement, alertes, historique\n- Ventes : créer, voir, imprimer, annuler\n- Recherche IMEI et scanner\n- Partenaires : inviter, accepter, supprimer\n- Filtres, recherche, pagination\n- Export CSV\n- Mode compact, masquer valeurs, thème sombre\n- Rapport quotidien, comparaison vs hier\n\nPosez votre question ou cliquez sur une suggestion !"
    },
    {
        keywords: ['mobile', 'responsive', 'portable', 'smartphone', 'tablette'],
        response: "Mystate est optimisé pour mobile :\n- Le menu se replie dans un bouton hamburger (≡)\n- Les tableaux s'adaptent à la largeur de l'écran\n- Les boutons d'action s'empilent verticalement\n- Le scanner utilise la caméra arrière\n- Le chatbot passe en plein écran sur petit écran\n\nL'application peut aussi être installée comme une app (PWA) depuis le navigateur."
    },
    {
        keywords: ['pwa', 'installer', 'application', 'app', 'raccourci'],
        response: "Mystate est une PWA (Progressive Web App) :\n- Vous pouvez l'installer sur votre écran d'accueil\n- Sur Chrome : menu ⋮ → \"Installer l'application\"\n- Sur Safari iOS : Partager → \"Sur l'écran d'accueil\"\n- L'app s'ouvre comme une application native\n- Icône Mystate sur votre bureau"
    }
];

const defaultResponse = "Je ne suis pas sûr de comprendre votre question. Essayez par exemple :\n- Comment ajouter un téléphone ?\n- Comment créer une vente ?\n- Comment faire un ajustement de stock ?\n- Comment exporter en CSV ?\n- Comment rechercher un IMEI ?\n- Comment inviter un partenaire ?\n- Comment imprimer une facture ?";

const defaultSuggestions = [
    'Aide générale',
    'Ajouter un téléphone',
    'Créer une vente',
    'Ajuster le stock',
    'Recherche IMEI',
    'Export CSV',
    'Inviter partenaire',
    'Imprimer facture'
];

function initChatbot() {
    const btn = document.getElementById('chatbot-btn');
    const win = document.getElementById('chatbot-window');
    if (!btn || !win) return;

    btn.addEventListener('click', () => {
        win.classList.toggle('active');
        if (win.classList.contains('active')) {
            const input = win.querySelector('.chatbot-input input');
            if (input) input.focus();
        }
    });

    const closeBtn = win.querySelector('.chatbot-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => win.classList.remove('active'));
    }

    const input = win.querySelector('.chatbot-input input');
    const sendBtn = win.querySelector('.chatbot-input button');

    if (input && sendBtn) {
        sendBtn.addEventListener('click', () => sendMessage(input));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') sendMessage(input);
        });
    }

    // Show suggestions
    showSuggestions(defaultSuggestions);
}

function sendMessage(input) {
    const text = input.value.trim();
    if (!text) return;

    addMessage(text, 'user');
    input.value = '';

    const response = findResponse(text);

    setTimeout(() => {
        addMessage(response, 'bot');
    }, 300);
}

function sendSuggestion(text) {
    addMessage(text, 'user');
    const response = findResponse(text);
    setTimeout(() => {
        addMessage(response, 'bot');
    }, 300);
}

function findResponse(input) {
    const lower = input.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    let bestMatch = null;
    let bestScore = 0;

    for (const item of chatbotResponses) {
        let score = 0;
        for (const kw of item.keywords) {
            const kwNorm = kw.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            if (lower.includes(kwNorm)) {
                score += kwNorm.length;
            }
        }
        if (score > bestScore) {
            bestScore = score;
            bestMatch = item;
        }
    }

    return bestMatch ? bestMatch.response : defaultResponse;
}

function addMessage(text, type) {
    const container = document.querySelector('.chatbot-messages');
    if (!container) return;

    const suggestions = document.querySelector('.chatbot-suggestions');
    if (suggestions) suggestions.innerHTML = '';

    const msg = document.createElement('div');
    msg.className = 'chatbot-msg ' + type;
    msg.textContent = text;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;

    if (type === 'bot') {
        setTimeout(() => showSuggestions(defaultSuggestions), 100);
    }
}

function showSuggestions(items) {
    const container = document.querySelector('.chatbot-suggestions');
    if (!container) return;

    container.innerHTML = '';
    items.forEach(text => {
        const btn = document.createElement('button');
        btn.textContent = text;
        btn.addEventListener('click', () => sendSuggestion(text));
        container.appendChild(btn);
    });
}

document.addEventListener('DOMContentLoaded', initChatbot);
