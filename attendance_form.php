<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
exiger_authentification();

// Ce fichier est inclus dans prof.php pour afficher le formulaire d'appel
// Récupération des élèves de la classe
$classe_id = $course['classe_id'];
$planning_id = $course['planning_id'];

// Récupérer les données de signature existantes pour ce cours
// Cette étape récupère les données de signature existantes pour le cours en cours
$signature_data = [];
try {
    $sql_existing_signatures = "
        SELECT user_id, statut_presence, commentaire 
        FROM signature 
        WHERE planning_id = :planning_id";
    $stmt_existing = $pdo->prepare($sql_existing_signatures);
    $stmt_existing->execute(['planning_id' => $planning_id]);
    while ($row = $stmt_existing->fetch(PDO::FETCH_ASSOC)) {
        $signature_data[$row['user_id']] = [
            'statut' => $row['statut_presence'],
            'commentaire' => $row['commentaire']
        ];
    }
} catch (PDOException $e) {
    // Affiche un message d'erreur si la récupération échoue
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des signatures existantes: " . $e->getMessage() . "</div>";
}

// Récupération des élèves de la classe
// Cette étape récupère la liste des élèves de la classe en cours
try {
    $sql_students = "
        SELECT IdUsers, Nom, prenom
        FROM users
        WHERE classe_id = :classe_id
        AND role = 'etudiant'
        ORDER BY Nom, prenom";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute(['classe_id' => $classe_id]);
    $students = $stmt_students->fetchAll();

    if (!empty($students)): ?>
        <!-- Formulaire d'appel pour enregistrer la présence des élèves -->
        <!-- Ce formulaire permet d'enregistrer la présence des élèves pour le cours en cours -->
        <form action="enregistrement_presence.php" method="POST" class="attendance-form">
            <input type="hidden" name="classe_id" value="<?= htmlspecialchars($classe_id) ?>">
            <input type="hidden" name="course_date" value="<?= htmlspecialchars($course['debut_du_cours']) ?>">
            <input type="hidden" name="planning_id" value="<?= htmlspecialchars($planning_id) ?>">

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Liste des élèves - <?= htmlspecialchars($course['classe']) ?></h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input select-all-present" type="checkbox" role="switch" id="selectAllPresent-<?= htmlspecialchars($planning_id) ?>">
                        <label class="form-check-label" for="selectAllPresent-<?= htmlspecialchars($planning_id) ?>">Tous présents</label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th class="text-center">Présent</th>
                            <th class="text-center">Retard</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center">Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            // Déterminer le statut existant pour chaque élève
                            $user_id = $student['IdUsers'];
                            $statut = $signature_data[$user_id]['statut'] ?? '';
                            $commentaire = $signature_data[$user_id]['commentaire'] ?? '';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($student['Nom']) ?></td>
                            <td><?= htmlspecialchars($student['prenom']) ?></td>
                            <td class="text-center">
                                <input type="radio" name="presence[<?= $user_id ?>]" value="present" <?= $statut === 'present' ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio" name="presence[<?= $user_id ?>]" value="retard" <?= $statut === 'retard' ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio" name="presence[<?= $user_id ?>]" value="absent" <?= $statut === 'absent' ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="text" name="comment[<?= $user_id ?>]" value="<?= htmlspecialchars($commentaire) ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <span class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Les élèves marqués comme présents ou en retard devront confirmer leur présence.
                    </span>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer l'appel</button>
            </div>
        </form>

        <script>
            // Script pour le bouton "Tous présents"
            // Ce script permet de sélectionner tous les élèves comme présents
            document.getElementById('selectAllPresent-<?= htmlspecialchars($planning_id) ?>').addEventListener('change', function() {
                const radios = document.querySelectorAll('input[type="radio"][value="present"]');
                radios.forEach(radio => radio.checked = this.checked);
            });
        </script>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Aucun étudiant trouvé pour cette classe.
        </div>
    <?php endif;
} catch (PDOException $e) {
    // Affiche un message d'erreur si la récupération des élèves échoue
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des étudiants : " . $e->getMessage() . "</div>";
}
?>