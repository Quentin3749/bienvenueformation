
-- script installation BDD

--- table classe

CREATE TABLE IF NOT EXISTS `users`(
IdUsers INT PRIMARY key NOT NULL AUTO_INCREMENT,
Nom VARCHAR(255),
prenom VARCHAR(255),
mail VARCHAR(255),
mp VARCHAR(255), 
classe_id INT,
role VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS `signature`(
id INT PRIMARY key NOT NULL AUTO_INCREMENT,
user_id INT,
planning_id INT,
nom_du_fichier VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS `planning` (
id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
matiere_id INT,
classe_id INT,
prof_id INT,
debut_du_cours DATETIME,
fin_du_cours DATETIME
);

CREATE TABLE IF NOT EXISTS `classe`(
Id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
Name VARCHAR (255)
);

--table matiere
CREATE TABLE IF NOT EXISTS `matiere` (
Id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
Name VARCHAR (255)
);

--table planning










