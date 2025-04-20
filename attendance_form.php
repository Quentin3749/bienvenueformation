<?php
// Ce fichier est inclus dans prof.php pour afficher le formulaire d'appel
// Récupération des élèves de la classe
$classe_id = $course['classe_id'];
$planning_id = $course['planning_id'];

// Récupérer les données de signature existantes pour ce cours
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
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des signatures existantes: " . $e->getMessage() . "</div>";
}

// Récupération des élèves de la classe
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
        <form action="save_attendance.php" method="POST" class="attendance-form">
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
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            // Déterminer le statut existant
                            $existingStatus = isset($signature_data[$student['IdUsers']]) ? $signature_data[$student['IdUsers']]['statut'] : '';
                            $existingComment = isset($signature_data[$student['IdUsers']]) ? $signature_data[$student['IdUsers']]['commentaire'] : '';
                            
                            // Convertir les statuts 'a_confirmer' ou 'validee' en 'present' pour l'affichage
                            if ($existingStatus === 'a_confirmer' || $existingStatus === 'validee') {
                                $existingStatus = 'present';
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($student['Nom']) ?></td>
                                <td><?= htmlspecialchars($student['prenom']) ?></td>
                                <td class="text-center">
                                    <input type="radio"
                                           name="presence[<?= $student['IdUsers'] ?>]"
                                           value="present"
                                           <?= ($existingStatus === 'present' || $existingStatus === '' ? 'checked' : '') ?>
                                           class="form-check-input presence-radio">
                                </td>
                                <td class="text-center">
                                    <input type="radio"
                                           name="presence[<?= $student['IdUsers'] ?>]"
                                           value="retard"
                                           <?= ($existingStatus === 'retard' ? 'checked' : '') ?>
                                           class="form-check-input presence-radio">
                                </td>
                                <td class="text-center">
                                    <input type="radio"
                                           name="presence[<?= $student['IdUsers'] ?>]"
                                           value="absent"
                                           <?= ($existingStatus === 'absent' ? 'checked' : '') ?>
                                           class="form-check-input presence-radio">
                                </td>
                                <td>
                                    <input type="text"
                                           name="comment[<?= $student['IdUsers'] ?>]"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($existingComment) ?>"
                                           placeholder="Commentaire optionnel">
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
                <div>
                    <button type="button" class="btn btn-secondary me-2 close-attendance-form">
                        <i class="bi bi-x-lg"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Enregistrer l'appel
                    </button>
                </div>
            </div>
        </form>

        <script>
            // Script pour le bouton "Tous présents"
            document.getElementById('selectAllPresent-<?= htmlspecialchars($planning_id) ?>').addEventListener('change', function() {
                const currentForm = this.closest('form');
                const presentRadios = currentForm.querySelectorAll('input[value="present"]');
                
                if (this.checked) {
                    presentRadios.forEach(radio => {
                        radio.checked = true;
                    });
                }
            });
            
            // Fermer le formulaire quand on clique sur Annuler
            document.querySelectorAll('.close-attendance-form').forEach(button => {
                button.addEventListener('click', function() {
                    const studentRow = this.closest('.student-list');
                    const toggleButton = document.querySelector(`[data-index="${studentRow.id.replace('students-', '')}"]`);
                    
                    // Cacher la ligne et changer le bouton
                    studentRow.style.display = 'none';
                    if (toggleButton.textContent.includes('Modifier')) {
                        toggleButton.innerHTML = '<i class="bi bi-pencil-square"></i> Modifier';
                    } else {
                        toggleButton.innerHTML = '<i class="bi bi-clipboard-check"></i> Faire l\'appel';
                    }
                    toggleButton.classList.replace('btn-secondary', 'btn-primary');
                });
            });
        </script>
    <?php else: ?>
        <div class="alert alert-info">