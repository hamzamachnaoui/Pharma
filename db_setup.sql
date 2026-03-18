CREATE DATABASE IF NOT EXISTS pharma_gestion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharma_gestion;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(150) NOT NULL,
    role ENUM('admin','pharmacien') DEFAULT 'pharmacien',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO utilisateurs (username, password, nom, role) VALUES
('admin', '$2y$10$Uh/IUAOCvu6HlPP4Uq3g4uJaFEEJitTVLVJsOJZioZ0.HRQfZNW.G', 'Administrateur', 'admin'),
('pharmacien', '$2y$10$6VD4iIF8ttCtnXePVGLWBeCT30xNVnCXEc93LGclkUEgvGcqgEQfi', 'Pharmacien', 'pharmacien');

CREATE TABLE fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    contact VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(100),
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    forme VARCHAR(50),
    dosage VARCHAR(50),
    categorie VARCHAR(100),
    fournisseur_id INT,
    prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_vente DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_actuel INT NOT NULL DEFAULT 0,
    stock_min INT NOT NULL DEFAULT 10,
    date_expiration DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    prenom VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(100),
    date_naissance DATE,
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

CREATE TABLE vente_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vente_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE CASCADE
);

INSERT INTO fournisseurs (nom, contact, telephone, email, adresse) VALUES
('Pharma Maroc', 'Ahmed Ben', '0522-000000', 'contact@pharma.ma', 'Casablanca, Maroc'),
('MedSupply', 'Fatima Al', '0537-000000', 'info@medy.ma', 'Rabat, Maroc'),
('SantéDistrib', 'Youssef Tahi', '0524-000000', 'youssef@santedi.ma', 'Marrakech, Maroc');

INSERT INTO medicaments (nom, forme, dosage, categorie, fournisseur_id, prix_achat, prix_vente, stock_actuel, stock_min, date_expiration) VALUES
('Paracétamol', 'Comprimé', '500mg', 'Antalgique', 1, 8.50, 15.00, 200, 50, '2026-12-31'),
('Amoxicilline', 'Gélule', '500mg', 'Antibiotique', 1, 25.00, 45.00, 80, 20, '2025-09-30'),
('Ibuprofène', 'Comprimé', '400mg', 'Anti-inflammatoire', 2, 12.00, 22.00, 150, 30, '2026-06-30'),
('Oméprazole', 'Gélule', '20mg', 'Gastrique', 2, 18.00, 35.00, 60, 15, '2025-11-30'),
('Metformine', 'Comprimé', '850mg', 'Diabète', 3, 20.00, 38.00, 5, 20, '2026-03-31'),
('Doliprane', 'Sirop', '2.4%', 'Antalgique', 1, 15.00, 28.00, 90, 25, '2025-08-31');

INSERT INTO clients (nom, prenom, telephone, email, date_naissance) VALUES
('Ben', 'Karim', '0661-000000', 'karim@gmail.com', '1985-04-12'),
('Tazin', 'Samira', '0662-000000', 'samira@gmail.com', '1992-07-23'),
('Mouss', 'Hassan', '0663-000000', NULL, '1978-01-05');

INSERT INTO ventes (client_id, total, note) VALUES
(1, 60.00, NULL),
(2, 80.00, 'Ordonnance Dr. Alami'),
(NULL, 15.00, 'Client anonyme');

INSERT INTO vente_items (vente_id, medicament_id, quantite, prix_unitaire) VALUES
(1, 1, 2, 15.00), (1, 3, 1, 22.00), (1, 4, 1, 23.00),
(2, 2, 1, 45.00), (2, 1, 1, 15.00), (2, 5, 1, 20.00),
(3, 1, 1, 15.00);
