-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 29 jan. 2025 à 13:37
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bienvenueformation`
--

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `Id` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`Id`, `Name`) VALUES
(9, 'master développement '),
(10, 'licence devellopeur web'),
(11, 'bts sio '),
(12, 'dut design');

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

CREATE TABLE `matiere` (
  `Id` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matiere`
--

INSERT INTO `matiere` (`Id`, `Name`) VALUES
(6, 'science'),
(7, 'francais'),
(8, 'physique'),
(9, 'développement '),
(10, 'economie social');

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

CREATE TABLE `planning` (
  `id` int(11) NOT NULL,
  `matiere_id` int(11) DEFAULT NULL,
  `classe_id` int(11) DEFAULT NULL,
  `prof_id` int(11) DEFAULT NULL,
  `debut_du_cours` datetime DEFAULT NULL,
  `fin_du_cours` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `planning`
--

INSERT INTO `planning` (`id`, `matiere_id`, `classe_id`, `prof_id`, `debut_du_cours`, `fin_du_cours`) VALUES
(8, 7, 12, 36, '2025-01-04 01:52:00', '2025-01-04 02:52:00'),
(9, 6, 9, 37, '2025-01-09 02:08:00', '2025-01-09 03:08:00'),
(10, 8, 12, 35, '2025-01-18 03:19:00', '2025-01-18 05:19:00'),
(11, 9, 11, 34, '2025-01-16 15:41:00', '2025-01-05 16:41:00');

-- --------------------------------------------------------

--
-- Structure de la table `signature`
--

CREATE TABLE `signature` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `planning_id` int(11) DEFAULT NULL,
  `nom_du_fichier` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `IdUsers` int(11) NOT NULL,
  `Nom` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `mp` varchar(255) DEFAULT NULL,
  `classe_id` int(11) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`IdUsers`, `Nom`, `prenom`, `mail`, `mp`, `classe_id`, `role`) VALUES
(23, 'Etourmy', 'Quentin', 'q.etourmy@gmail.com', '$2y$10$fYgOyH35LM.8rLBkfbTARe50hq54OTd2SlXILnEPzxIsPb6Tr3NO6', 11, 'admin'),
(24, 'Potter', 'Harry', 'harrypotter@gmail.com', '$2y$10$7F4a/LoSC9S4XGeiG2QVteIshhR73k2b8TUFMcaQmAP3K7lHCD/na', 11, 'etudiant'),
(25, 'Morales', 'Miles', 'milesmorales@gmail.com', '$2y$10$urbbGC7/xg2YVN7rbbAJguIc6MrX2YRX0ygIPrxNDVSQFEVn3DnKW', 11, 'etudiant'),
(26, 'Simpson', 'Lisa', 'lisasimpson@gmail.com', '$2y$10$MzpZPFxWzHvCpTz9Hi8/jOLH9DviZj3MVxBlb9oOm8ZKT0ZyqikGW', 12, 'etudiant'),
(27, 'Son', 'Gohan', 'songohan@gmail.com', '$2y$10$qeoMcxfL/pPEBZWt/exGm.3KJYDen1ux/jaJXX4xQLvQEGj9sQuxG', 12, 'etudiant'),
(28, 'Uzumaki', 'Naruto', 'narutouzumaki@gmail.com', '$2y$10$U1/RKfGT973kknALbfAiu.lGDIJABhM8kUguFRuY7UfETXt7mWMKq', 9, 'etudiant'),
(29, 'Muto', 'Yugi', 'yugimuto@gmail.com', '$2y$10$PDdhiRfWfd6ccicr.lDRcO0sAHaeYSChbRe0AJePFZJmp1QodvfLy', 9, 'etudiant'),
(30, 'Yagami', 'Light', 'lightyagami@gmail.com', '$2y$10$HL1E2ou0cbKjQzL810tlVe6SMfGI6JQTj1nEmgbbSHI6CNQAiqQxS', 10, 'etudiant'),
(31, 'Jackson', 'Percy', 'percyjackson@gmail.com', '$2y$10$Phs3lOgBqhj2MctsR80uBe79m7gmtmX5GfqUwFMQRR8HSQ2ZOu8PK', 10, 'etudiant'),
(32, 'Hopkins', 'Jimmy', 'jimmyhopkins@gmail.com', '$2y$10$gztFco1OXhvfH3J8ZB0Ene/Mv9sPE7SKxVQ8TBzFXASJcREQ05Cp6', 11, 'etudiant'),
(33, 'Parker', 'Peter', 'peterparker@gmail.com', '$2y$10$KJSx13Ey77iKpU4Ga8SToOjTeOxUF90iU4xT4xKS.geJOnEVcmQOi', NULL, 'etudiant'),
(34, 'Rogue', 'Severus', 'severusrogue@gmail.com', '$2y$10$45ubA7sGeY7TYiQPGT1HFejE39O5U5TdGEPD1OkJE.ejZbWJpPtMS', NULL, 'enseignant'),
(35, 'Xavier', 'Charles', 'charlesxavier@gmail.com', '$2y$10$KfQ/4sYeELO5yWU6wzBeXuZrmmgYr6r28W/AH0aT0/zQm1sVVfNuy', NULL, 'enseignant'),
(36, 'Keating', 'John', 'johnkeating@gmail.com', '$2y$10$Q5cVYFMO3osPpLf04fHTWung71GN8VUoVnXH.BIrXtsI9tHwCvrVK', NULL, 'enseignant'),
(37, 'White', 'Walter', 'walterwhite@gmail.com', '$2y$10$ek/dYHkeShJ2dO5Bf5HJIO8KklSy4jXx6CUSD940AlA3eKRtJokOS', NULL, 'enseignant'),
(38, 'house', 'gregory', 'gregoryhouse@gmail.com', '$2y$10$IBw92MjzDDm6WypXAD2M6eyxsWDujbTX5/sVxj87fFEoIX8FBbE0K', NULL, 'enseignant'),
(39, 'sensei', 'koro', 'korosensei@gmail.com', '$2y$10$hmzzBYb05v5QlZcse1DBkuQOT/dcvzCcmZ4rEEUqJYtuVOrNZOqSe', NULL, 'enseignant'),
(40, 'Holmes', 'Sherlock', 'sherlockholmes@gmail.com', '$2y$10$9uHwNE/s8v1HrBo7o/YyLuT2cTTrQRhq8M5h9f7RIXTD7Pj8IB5Ti', 12, 'enseignant');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prof_id` (`prof_id`),
  ADD KEY `classe_id` (`classe_id`),
  ADD KEY `matiere_id` (`matiere_id`);

--
-- Index pour la table `signature`
--
ALTER TABLE `signature`
  ADD PRIMARY KEY (`id`),
  ADD KEY `signature_ibfk_1` (`planning_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`IdUsers`),
  ADD KEY `classe_id` (`classe_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `classe`
--
ALTER TABLE `classe`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `matiere`
--
ALTER TABLE `matiere`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `signature`
--
ALTER TABLE `signature`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `IdUsers` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `planning_ibfk_1` FOREIGN KEY (`prof_id`) REFERENCES `users` (`IdUsers`),
  ADD CONSTRAINT `planning_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`Id`),
  ADD CONSTRAINT `planning_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matiere` (`Id`);

--
-- Contraintes pour la table `signature`
--
ALTER TABLE `signature`
  ADD CONSTRAINT `signature_ibfk_1` FOREIGN KEY (`planning_id`) REFERENCES `planning` (`id`),
  ADD CONSTRAINT `signature_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`IdUsers`);

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
