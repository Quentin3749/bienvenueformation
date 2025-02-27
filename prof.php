<?php
session_start();
include_once "connect_ddb.php";

// Fonction pour récupérer les données du professeur
function getProfessorData($email, $pdo) {
    $sql = "SELECT idUsers, prenom FROM users WHERE mail = :email AND role = 'enseignant'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    return $stmt->fetch();
}

// Fonction pour récupérer les cours du professeur
function getCourses($prof_id, $pdo) {
    $sql_courses = "
        SELECT 
            m.Name AS matiere, 
            c.Name AS classe, 
            c.Id AS classe_id, 
            p.debut_du_cours, 
            p.fin_du_cours
        FROM planning p
        INNER JOIN matiere m ON p.matiere_id = m.Id
        INNER JOIN classe c ON p.classe_id = c.Id
        WHERE p.prof_id = :prof_id
        ORDER BY p.debut_du_cours";
    
    $stmt_courses = $pdo->prepare($sql_courses);
    $stmt_courses->execute(['prof_id' => $prof_id]);
    return $stmt_courses->fetchAll();
}

// Vérification de l'authentification
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $profData = getProfessorData($email, $pdo);
    
    if ($profData) {
        $prof_id = $profData['idUsers'];
        $prenom = $profData['prenom'];
        $courses = getCourses($prof_id, $pdo);
    } else {
        $prenom = "Utilisateur inconnu";
        $courses = [];
    }
} else {
    $prenom = "Utilisateur inconnu";
    $courses = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Professeur</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .student-list {
            display: none;
        }
        .presence-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .table th {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-calendar-check"></i> Gestion des présences
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto">
                    <?php if ($prenom !== "Utilisateur inconnu"): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($prenom) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>
                    <i class="bi bi-calendar3"></i>
                    Cours de <?= htmlspecialchars($prenom) ?>
                </h2>
                
                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Début du cours</th>
                                    <th>Fin du cours</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $index => $course): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-book"></i>
                                            <?= htmlspecialchars($course['matiere']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-people"></i>
                                            <?= htmlspecialchars($course['classe']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock"></i>
                                            <?= htmlspecialchars($course['debut_du_cours']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock-history"></i>
                                            <?= htmlspecialchars($course['fin_du_cours']) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary toggle-students" data-index="<?= $index ?>">
                                                <i class="bi bi-clipboard-check"></i> Faire l'appel
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Liste des élèves -->
                                    <tr id="students-<?= $index ?>" class="student-list">
                                        <td colspan="5" class="presence-form">
                                            <?php
                                            $classe_id = $course['classe_id'];
                                            
                                            // Récupération des élèves de la classe
                                            $sql_students = "
                                                SELECT idUsers, Nom, prenom 
                                                FROM users 
                                                WHERE classe_id = :classe_id 
                                                AND role = 'etudiant'
                                                ORDER BY Nom, prenom";
                                            $stmt_students = $pdo->prepare($sql_students);
                                            $stmt_students->execute(['classe_id' => $classe_id]);
                                            $students = $stmt_students->fetchAll();
                                            
                                            if (!empty($students)): ?>
                                                <form action="save_attendance.php" method="POST" class="attendance-form">
                                                    <input type="hidden" name="classe_id" value="<?= htmlspecialchars($classe_id) ?>">
                                                    <input type="hidden" name="course_date" value="<?= htmlspecialchars($course['debut_du_cours']) ?>">
                                                    
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Nom</th>
                                                                <th>Prénom</th>
                                                                <th class="text-center">Présent</th>
                                                                <th class="text-center">Retard</th>
                                                                <th class="text-center">Absent</th>
                                                                <th>Commentaire</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($students as $student): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($student['Nom']) ?></td>
                                                                    <td><?= htmlspecialchars($student['prenom']) ?></td>
                                                                    <td class="text-center">
                                                                        <input type="radio" 
                                                                               name="presence[<?= $student['idUsers'] ?>]" 
                                                                               value="present" 
                                                                               checked 
                                                                               class="form-check-input">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="radio" 
                                                                               name="presence[<?= $student['idUsers'] ?>]" 
                                                                               value="retard" 
                                                                               class="form-check-input">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="radio" 
                                                                               name="presence[<?= $student['idUsers'] ?>]" 
                                                                               value="absent" 
                                                                               class="form-check-input">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" 
                                                                               name="comment[<?= $student['idUsers'] ?>]" 
                                                                               class="form-control form-control-sm" 
                                                                               placeholder="Commentaire optionnel">
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                    <div class="text-end mb-3">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="bi bi-check-lg"></i> Enregistrer l'appel
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i>
                                                    Aucun élève n'est inscrit dans cette classe.
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Aucun cours disponible pour ce professeur.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour gérer l'affichage/masquage de la liste des élèves -->
    <script>
        document.querySelectorAll('.toggle-students').forEach(button => {
            button.addEventListener('click', () => {
                const index = button.getAttribute('data-index');
                const row = document.getElementById(`students-${index}`);
                
                // Toggle l'affichage
                if (row.style.display === 'none' || row.style.display === '') {
                    row.style.display = 'table-row';
                    button.innerHTML = '<i class="bi bi-clipboard-x"></i> Masquer la liste';
                    button.classList.replace('btn-primary', 'btn-secondary');
                } else {
                    row.style.display = 'none';
                    button.innerHTML = '<i class="bi bi-clipboard-check"></i> Faire l\'appel';
                    button.classList.replace('btn-secondary', 'btn-primary');
                }
            });
        });
    </script>
</body>
</html>
