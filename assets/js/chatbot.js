/**
 * Mystate Chatbot - Assistant d'aide intégré
 * Réponses prédéfinies sur le fonctionnement de l'application
 */

const chatbotResponses = [
    {
        keywords: ['bonjour', 'salut', 'hello', 'hey', 'bonsoir', 'coucou'],
        response: "Bonjour ! Je suis l'assistant Mystate. Comment puis-je vous aider ? Vous pouvez me poser des questions sur les fonctionnalités de l'application."
    },
    {
        keywords: ['merci', 'super', 'parfait', 'ok', 'compris'],
        response: "Avec plaisir ! N'hésitez pas si vous avez d'autres questions."
    },
    // Navigation
    {
        keywords: ['tableau de bord', 'dashboard', 'accueil'],
        response: "Le Tableau de bord affiche un résumé de votre activité :\n- Stock total, références, valeur du stock\n- Ventes et factures du mois\n- Rapport du jour avec comparaison vs hier\n- Alertes stock bas et derniers mouvements\n\nCliquez sur \"Tableau de bord\" dans le menu pour y accéder."
    },
    {
        keywords: ['menu', 'navigation', 'navbar', 'barre'],
        response: "La barre de navigation en haut contient :\n- Tableau de bord : vue d'ensemble\n- Téléphones : gérer vos produits\n- Stock : mouvements d'entrée/sortie\n- Ventes : factures et historique\n- Partenaires : partage de stock\n- IMEI : recherche par numéro IMEI\n- Icône soleil/lune : mode sombre"
    },
    // Téléphones
    {
        keywords: ['ajouter', 'nouveau téléphone', 'créer téléphone', 'ajout'],
        response: "Pour ajouter un téléphone :\n1. Allez dans Téléphones\n2. Cliquez sur \"+ Ajouter un téléphone\"\n3. Remplissez : marque, modèle, prix, quantité, stock minimum\n4. Cliquez sur Enregistrer"
    },
    {
        keywords: ['modifier', 'éditer', 'edit', 'changer'],
        response: "Pour modifier un téléphone :\n1. Allez dans la liste des Téléphones\n2. Trouvez le produit dans le tableau\n3. Cliquez sur le bouton \"Modifier\" (dans la colonne Actions)\n4. Modifiez les informations et enregistrez"
    },
    {
        keywords: ['supprimer', 'effacer', 'delete', 'enlever'],
        response: "Pour supprimer un téléphone :\n1. Allez dans la liste des Téléphones\n2. Cliquez sur \"Supprimer\" (bouton rouge)\n3. Confirmez la suppression dans la popup\n\nAttention : cette action est irréversible !"
    },
    {
        keywords: ['téléphone', 'produit', 'liste', 'inventaire'],
        response: "La page Téléphones affiche tous vos produits :\n- Recherche par modèle ou code-barres\n- Filtre par marque et statut stock\n- Bouton scanner pour code-barres\n- Export CSV pour télécharger la liste\n- Bouton Compact pour réduire le tableau"
    },
    // Stock
    {
        keywords: ['stock', 'mouvement', 'entrée', 'sortie', 'ajuster', 'réappro'],
        response: "Pour gérer le stock :\n- \"+ Nouveau mouvement\" : ajouter une entrée ou sortie\n- Bouton \"Stock\" sur chaque téléphone : ajustement rapide\n- Bouton \"Réappro\" sur les alertes stock bas\n\nLa page Mouvements affiche l'historique avec filtres par téléphone, type et date."
    },
    {
        keywords: ['stock bas', 'alerte', 'minimum', 'rupture'],
        response: "Les alertes stock bas apparaissent quand la quantité d'un produit est inférieure ou égale au stock minimum défini.\n\n- Badge rouge \"Stock bas\" dans la liste\n- Section dédiée sur le Tableau de bord\n- Bouton \"Réappro\" pour réapprovisionner rapidement"
    },
    // Ventes
    {
        keywords: ['vente', 'facture', 'vendre', 'créer vente', 'nouvelle vente'],
        response: "Pour créer une vente :\n1. Allez dans Ventes\n2. Cliquez sur \"+ Nouvelle vente\"\n3. Remplissez le nom du client\n4. Ajoutez les produits avec quantités\n5. Validez la facture\n\nLa facture est générée automatiquement avec un numéro unique."
    },
    {
        keywords: ['annuler', 'cancel', 'annulation'],
        response: "Pour annuler une facture :\n1. Allez dans Ventes\n2. Cliquez sur \"Annuler\" (bouton rouge) à côté de la facture\n3. Confirmez l'annulation\n\nLe stock est automatiquement restauré après annulation."
    },
    {
        keywords: ['imprimer', 'impression', 'print', 'pdf'],
        response: "Pour imprimer une facture :\n1. Dans la liste des Ventes, cliquez sur \"Imprimer\"\n   OU dans le détail d'une facture, cliquez sur \"Imprimer\"\n2. La facture s'ouvre dans un nouvel onglet\n3. Utilisez Ctrl+P pour imprimer ou sauvegarder en PDF"
    },
    {
        keywords: ['voir', 'détail', 'consulter facture'],
        response: "Pour voir le détail d'une facture :\n1. Dans la liste des Ventes\n2. Cliquez sur \"Voir\" à côté de la facture\n3. Vous verrez : infos facture, client, lignes de produits, IMEI associés, total"
    },
    // IMEI
    {
        keywords: ['imei', 'recherche imei', 'numéro série'],
        response: "La recherche IMEI permet de tracer un téléphone :\n1. Cliquez sur \"IMEI\" dans le menu\n2. Entrez le numéro IMEI (ou scannez)\n3. Résultat : téléphone associé, statut (en stock ou vendu)\n4. Si vendu : nom du client et n° de facture"
    },
    {
        keywords: ['scanner', 'code-barres', 'barcode', 'scan', 'caméra'],
        response: "Le bouton Scanner (icône code-barres) permet de :\n1. Utiliser la caméra pour scanner un code-barres/IMEI\n2. Le code est automatiquement rempli dans le champ de recherche\n3. Vous pouvez aussi saisir le code manuellement\n\nDisponible sur : liste Téléphones et recherche IMEI."
    },
    // Filtres et recherche
    {
        keywords: ['filtre', 'filtrer', 'recherche', 'chercher', 'trouver'],
        response: "Chaque page liste dispose de filtres :\n- Téléphones : recherche, marque, statut stock\n- Ventes : recherche, plage de dates, statut\n- Mouvements : recherche, téléphone, type, plage de dates\n\nCliquez \"Filtrer\" pour appliquer, \"Réinitialiser\" pour tout effacer."
    },
    {
        keywords: ['date', 'plage', 'période', 'date début', 'date fin'],
        response: "Les filtres par date fonctionnent avec une plage :\n- Date début : affiche les résultats à partir de cette date\n- Date fin : affiche les résultats jusqu'à cette date\n- Vous pouvez utiliser un seul des deux champs\n\nDisponible sur les pages Ventes et Mouvements."
    },
    // Export
    {
        keywords: ['csv', 'export', 'exporter', 'télécharger', 'excel'],
        response: "Le bouton \"CSV\" permet d'exporter les données :\n1. Appliquez vos filtres si besoin\n2. Cliquez sur le bouton CSV (icône téléchargement)\n3. Un fichier .csv est téléchargé avec les données filtrées\n\nDisponible sur : Téléphones, Ventes, Mouvements.\nLe fichier s'ouvre dans Excel ou LibreOffice."
    },
    // Affichage
    {
        keywords: ['compact', 'tableau compact', 'réduire', 'petit'],
        response: "Le bouton \"Compact\" réduit la taille des tableaux :\n- Moins d'espacement entre les lignes\n- Police plus petite\n- Permet de voir plus de données à l'écran\n\nCliquez à nouveau pour revenir en mode normal. Le choix est mémorisé."
    },
    {
        keywords: ['masquer', 'cacher', 'oeil', 'valeur', 'montant', 'afficher'],
        response: "Le bouton oeil (Masquer/Afficher) sur le Tableau de bord :\n- Masque tous les montants en Ar (valeur stock, ventes, chiffre du jour)\n- Remplace par des points pour la confidentialité\n- Cliquez à nouveau pour afficher\n- Le choix est mémorisé entre les visites"
    },
    {
        keywords: ['sombre', 'dark', 'thème', 'nuit', 'clair', 'mode'],
        response: "Pour changer le thème (clair/sombre) :\n- Cliquez sur l'icône soleil/lune dans le menu\n- Le mode sombre est plus confortable la nuit\n- Votre préférence est sauvegardée automatiquement"
    },
    {
        keywords: ['comparaison', 'hier', 'pourcentage', 'flèche'],
        response: "Sur le Tableau de bord, sous chaque stat du rapport du jour :\n- Flèche verte ↑ : hausse par rapport à hier\n- Flèche rouge ↓ : baisse par rapport à hier\n- = : identique à hier\n\nCela vous permet de suivre votre progression quotidienne."
    },
    // Partenaires
    {
        keywords: ['partenaire', 'partage', 'inviter', 'collaboration'],
        response: "La section Partenaires permet de :\n- Inviter un autre utilisateur à voir votre stock\n- Accepter/refuser des invitations\n- Partager la visibilité du stock entre partenaires\n\nAllez dans Partenaires pour gérer vos collaborations."
    },
    // Déconnexion
    {
        keywords: ['déconnexion', 'déconnecter', 'logout', 'quitter'],
        response: "Pour vous déconnecter :\n1. Cliquez sur \"Déconnexion\" dans le menu\n2. Confirmez dans la popup\n\nVotre session sera fermée et vous serez redirigé vers la page de connexion."
    },
    // Aide générale
    {
        keywords: ['aide', 'help', 'comment', 'quoi', 'fonctionnalité', 'faire'],
        response: "Voici ce que je peux vous expliquer :\n- Tableau de bord et ses statistiques\n- Gestion des téléphones (ajouter, modifier, supprimer)\n- Gestion du stock (entrées, sorties, alertes)\n- Ventes et factures\n- Recherche IMEI\n- Export CSV\n- Filtres et recherche\n- Mode compact, thème sombre, masquer valeurs\n\nPosez-moi votre question !"
    }
];

const defaultResponse = "Je ne suis pas sûr de comprendre votre question. Essayez de me demander :\n- Comment ajouter un téléphone ?\n- Comment créer une vente ?\n- Comment exporter en CSV ?\n- Comment fonctionne le stock ?\n- Comment rechercher un IMEI ?";

const defaultSuggestions = [
    'Tableau de bord',
    'Ajouter un téléphone',
    'Créer une vente',
    'Recherche IMEI',
    'Export CSV',
    'Stock bas',
    'Mode compact',
    'Masquer valeurs'
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

    // Find response
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

    // Clear suggestions when user sends a message
    const suggestions = document.querySelector('.chatbot-suggestions');
    if (suggestions) suggestions.innerHTML = '';

    const msg = document.createElement('div');
    msg.className = 'chatbot-msg ' + type;
    msg.textContent = text;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;

    // Show new suggestions after bot response
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
