CREATE TABLE config (
-- Configuration de Garradin
    cle TEXT PRIMARY KEY,
    valeur TEXT
);

-- On stocke ici les ID de catégorie de compta correspondant aux types spéciaux
-- compta_categorie_cotisations => id_categorie
-- compta_categorie_dons => id_categorie

CREATE TABLE membres_categories
-- Catégories de membres
(
    id INTEGER PRIMARY KEY,
    nom TEXT,
    description TEXT,

    droit_wiki INT DEFAULT 1,
    droit_membres INT DEFAULT 1,
    droit_compta INT DEFAULT 1,
    droit_inscription INT DEFAULT 0,
    droit_connexion INT DEFAULT 1,
    droit_config INT DEFAULT 0,
    cacher INT DEFAULT 0,

    id_transaction_obligatoire INTEGER NULL,

    FOREIGN KEY (id_transaction_obligatoire) REFERENCES transactions (id)
);

-- Membres de l'asso
-- Table dynamique générée par l'application
-- voir class.champs_membres.php

CREATE TABLE transactions
-- Paiements possibles
(
    id INTEGER PRIMARY KEY,
    id_categorie_compta INTEGER NULL, -- NULL si le type n'est pas associé automatiquement à la compta

    intitule TEXT NOT NULL,
    description TEXT NULL,
    montant REAL NOT NULL,

    duree INTEGER NULL, -- En jours
    debut TEXT NULL, -- timestamp
    fin TEXT NULL,

    FOREIGN KEY (id_categorie_compta) REFERENCES compta_categories (id)
);

CREATE TABLE rappels
-- Rappels de devoir renouveller une transaction
(
    id INTEGER PRIMARY KEY,
    id_transaction INTEGER NULL,

    delai INTEGER NOT NULL, -- Délai en jours pour envoyer le rappel

    sujet TEXT NOT NULL,
    texte TEXT NOT NULL,

    FOREIGN KEY (id_transaction) REFERENCES transactions (id)
);

CREATE TABLE rappels_envoyes
-- Enregistrement des rappels envoyés à qui et quand
(
    id_membre INTEGER NOT NULL,
    id_rappel INTEGER NOT NULL,
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    media INTEGER NOT NULL, -- Média utilisé pour le rappel : 1 = email, 2 = courrier, 3 = autre
    
    FOREIGN KEY (id_membre) REFERENCES membres (id),
    FOREIGN KEY (id_rappel) REFERENCES rappels (id),

    PRIMARY KEY(id_membre, id_rappel, date)
);

CREATE TABLE membres_transactions
-- Paiements enregistrés
(
    id_membre INTEGER NOT NULL,
    id_transaction INTEGER NULL, -- NULL si n'est pas relié à une transaction prévue

    libelle TEXT NULL,

    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant REAL NOT NULL,

    FOREIGN KEY (id_membre) REFERENCES membres (id),
    FOREIGN KEY (id_transaction) REFERENCES transactions (id)
);

CREATE TABLE membres_transactions_operations
-- Liaison paiements enregistrés avec écritures comptables
(
    id_operation INTEGER NOT NULL,
    id_membre_transaction INTEGER NOT NULL,

    FOREIGN KEY (id_operation) REFERENCES compta_journal (id),
    FOREIGN KEY (id_membre_transaction) REFERENCES membres_transactions (id)
);

--
-- WIKI
--

CREATE TABLE wiki_pages
-- Pages du wiki
(
    id INTEGER PRIMARY KEY,
    uri TEXT, -- URI unique (équivalent NomPageWiki)
    titre TEXT,
    date_creation TEXT DEFAULT CURRENT_TIMESTAMP,
    date_modification TEXT DEFAULT CURRENT_TIMESTAMP,
    parent INTEGER DEFAULT 0, -- ID de la page parent
    revision INTEGER DEFAULT 0, -- Numéro de révision (commence à 0 si pas de texte, +1 à chaque changement du texte)
    droit_lecture INTEGER DEFAULT 0, -- Accès en lecture (-1 = public [site web], 0 = tous ceux qui ont accès en lecture au wiki, 1+ = ID de groupe)
    droit_ecriture INTEGER DEFAULT 0 -- Accès en écriture (0 = tous ceux qui ont droit d'écriture sur le wiki, 1+ = ID de groupe)
);

CREATE UNIQUE INDEX wiki_uri ON wiki_pages (uri);

CREATE VIRTUAL TABLE wiki_recherche USING fts4
-- Table dupliquée pour chercher une page
(
    id INT PRIMARY KEY NOT NULL, -- Clé externe obligatoire
    titre TEXT,
    contenu TEXT, -- Contenu de la dernière révision
    FOREIGN KEY (id) REFERENCES wiki_pages(id)
);

CREATE TABLE wiki_revisions
-- Révisions du contenu des pages
(
    id_page INTEGER NOT NULL,
    revision INTEGER,

    id_auteur INTEGER,

    contenu TEXT,
    modification TEXT, -- Description des modifications effectuées
    chiffrement INTEGER DEFAULT 0, -- 1 si le contenu est chiffré, 0 sinon
    date TEXT DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY(id_page, revision),
    FOREIGN KEY (id_page) REFERENCES wiki_pages (id), -- Clé externe obligatoire
    FOREIGN KEY (id_auteur) REFERENCES membres (id)  -- Clé externe non-obligatoire (peut être supprimée après en cas de suppression de membre)
);

CREATE INDEX wiki_revisions_id_page ON wiki_revisions (id_page);
CREATE INDEX wiki_revisions_id_auteur ON wiki_revisions (id_auteur);

-- Triggers pour synchro avec table wiki_pages
CREATE TRIGGER wiki_recherche_delete AFTER DELETE ON wiki_pages
    BEGIN
        DELETE FROM wiki_recherche WHERE id = old.id;
    END;

CREATE TRIGGER wiki_recherche_update AFTER UPDATE OF id, titre ON wiki_pages
    BEGIN
        UPDATE wiki_recherche SET id = new.id, titre = new.titre WHERE id = old.id;
    END;

-- Trigger pour mettre à jour le contenu de la table de recherche lors d'une nouvelle révision
CREATE TRIGGER wiki_recherche_contenu_insert AFTER INSERT ON wiki_revisions WHEN new.chiffrement != 1
    BEGIN
        UPDATE wiki_recherche SET contenu = new.contenu WHERE id = new.id_page;
    END;

-- Si le contenu est chiffré, la recherche n'affiche pas de contenu
CREATE TRIGGER wiki_recherche_contenu_chiffre AFTER INSERT ON wiki_revisions WHEN new.chiffrement = 1
    BEGIN
        UPDATE wiki_recherche SET contenu = '' WHERE id = new.id_page;
    END;

/*
CREATE TABLE wiki_fichiers (
    id INTEGER PRIMARY KEY,
    id_page INTEGER NOT NULL,
    nom TEXT,
    hash TEXT,

    FOREIGN KEY (id_page) REFERENCES wiki_pages (id) -- Clé externe obligatoire
);

CREATE INDEX wiki_fichiers_id_page ON wiki_fichiers (id_page);

CREATE TABLE wiki_suivi
-- Suivi des pages
(
    id_membre INTEGER NOT NULL,
    id_page INTEGER NOT NULL,

    PRIMARY KEY (id_membre, id_page),

    FOREIGN KEY (id_page) REFERENCES wiki_pages (id), -- Clé externe obligatoire
    FOREIGN KEY (id_membre) REFERENCES membres (id) -- Clé externe obligatoire
);
*/

--
-- COMPTA
--

CREATE TABLE compta_exercices
-- Exercices
(
    id INTEGER PRIMARY KEY,

    libelle TEXT NOT NULL,

    debut TEXT NOT NULL DEFAULT CURRENT_DATE,
    fin TEXT NULL DEFAULT NULL,

    cloture INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE compta_comptes
-- Plan comptable
(
    id TEXT PRIMARY KEY, -- peut contenir des lettres, eg. 53A, 53B, etc.
    parent TEXT NOT NULL DEFAULT 0,

    libelle TEXT NOT NULL,

    position INTEGER NOT NULL, -- position actif/passif/charge/produit
    plan_comptable INTEGER NOT NULL DEFAULT 1, -- 1 = fait partie du plan comptable, 0 = a été ajouté par l'utilisateur
    desactive INTEGER NOT NULL DEFAULT 0 -- 1 = compte historique désactivé
);

CREATE INDEX compta_comptes_parent ON compta_comptes (parent);

CREATE TABLE compta_comptes_bancaires
-- Comptes bancaires
(
    id TEXT PRIMARY KEY,

    banque TEXT NOT NULL,

    iban TEXT,
    bic TEXT,

    FOREIGN KEY(id) REFERENCES compta_comptes(id)
);

CREATE TABLE compta_journal
-- Journal des opérations comptables
(
    id INTEGER PRIMARY KEY,

    libelle TEXT NOT NULL,
    remarques TEXT,
    numero_piece TEXT, -- N° de pièce comptable

    montant REAL,

    date TEXT DEFAULT CURRENT_DATE,
    moyen_paiement TEXT DEFAULT NULL,
    numero_cheque TEXT DEFAULT NULL,

    compte_debit TEXT, -- N° du compte dans le plan
    compte_credit TEXT, -- N° du compte dans le plan

    id_exercice INTEGER NULL DEFAULT NULL, -- En cas de compta simple, l'exercice est permanent (NULL)
    id_auteur INTEGER NULL,
    id_categorie INTEGER NULL, -- Numéro de catégorie (en mode simple)
    id_transaction INTEGER NULL, -- Numéro de transaction

    FOREIGN KEY(moyen_paiement) REFERENCES compta_moyens_paiement(code),
    FOREIGN KEY(compte_debit) REFERENCES compta_comptes(id),
    FOREIGN KEY(compte_credit) REFERENCES compta_comptes(id),
    FOREIGN KEY(id_exercice) REFERENCES compta_exercices(id),
    FOREIGN KEY(id_auteur) REFERENCES membres(id),
    FOREIGN KEY(id_categorie) REFERENCES compta_categories(id),
    FOREIGN KEY(id_transaction) REFERENCES membres_transactions(id)
);

CREATE INDEX compta_operations_exercice ON compta_journal (id_exercice);
CREATE INDEX compta_operations_date ON compta_journal (date);
CREATE INDEX compta_operations_comptes ON compta_journal (compte_debit, compte_credit);
CREATE INDEX compta_operations_auteur ON compta_journal (id_auteur);

CREATE TABLE compta_moyens_paiement
-- Moyens de paiement
(
    code TEXT PRIMARY KEY,
    nom TEXT
);

--INSERT INTO compta_moyens_paiement (code, nom) VALUES ('AU', 'Autre');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('CB', 'Carte bleue');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('CH', 'Chèque');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('ES', 'Espèces');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('PR', 'Prélèvement');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('TI', 'TIP');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('VI', 'Virement');

CREATE TABLE compta_categories
-- Catégories pour simplifier le plan comptable
(
    id INTEGER PRIMARY KEY,
    type INTEGER DEFAULT 1, -- 1 = recette, -1 = dépense, 0 = autre (utilisé uniquement pour l'interface)

    intitule TEXT NOT NULL,
    description TEXT,

    compte TEXT NOT NULL, -- Compte affecté par cette catégorie

    FOREIGN KEY(compte) REFERENCES compta_comptes(id)
);

CREATE TABLE plugins
(
    id TEXT PRIMARY KEY,
    officiel INTEGER NOT NULL DEFAULT 0,
    nom TEXT NOT NULL,
    description TEXT,
    auteur TEXT,
    url TEXT,
    version TEXT NOT NULL,
    menu INTEGER NOT NULL DEFAULT 0,
    config TEXT
);