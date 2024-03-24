<?php
include('header.php');
include('config.php');

// CREATE Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $name = htmlspecialchars($_POST['name']);
    $project_id = $_POST['project_id'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $status = 'ready'; // statut par défaut

    // Validation des données (à adapter selon vos besoins)
    if (empty($name) || empty($project_id)) {
        echo '<p>Veuillez remplir tous les champs.</p>';
    } else {
        // Vérifier si la tâche existe déjà
        $sql_check_task = $conn->prepare("SELECT * FROM tasks WHERE name = ? AND project_id = ?");
        $sql_check_task->bind_param("si", $name, $project_id);
        $sql_check_task->execute();
        $result_check_task = $sql_check_task->get_result();

        if ($result_check_task->num_rows > 0) {
            echo '<p>La tâche existe déjà.</p>';
        } else {
            // Utilisation d'une requête préparée pour éviter l'injection SQL
            $sql_create_task = $conn->prepare("INSERT INTO tasks (name, project_id, user_id, status) VALUES (?, ?, ?, ?)");
            $sql_create_task->bind_param("siss", $name, $project_id, $user_id, $status);

            if ($sql_create_task->execute()) {
                echo '<p>Tâche ajoutée avec succès.</p>';
            } else {
                echo '<p>Erreur lors de l\'ajout de la tâche : ' . $conn->error . '</p>';
            }

            $sql_create_task->close();
        }

        $sql_check_task->close();
    }
}


// DELETE Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id_to_delete = $_POST['task_id_to_delete'];

    // Validation des données (à adapter selon vos besoins)
    if (!empty($task_id_to_delete) && is_numeric($task_id_to_delete)) {
        // Supprimer les membres liés à la tâche
        $sql_delete_members = "DELETE FROM members WHERE task_id = $task_id_to_delete";
        if ($conn->query($sql_delete_members) === TRUE) {
            // echo '<p>Membres de la tâche supprimés avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression des membres de la tâche : ' . $conn->error . '</p>';
        }

        // Enfin, supprimer la tâche elle-même
        $sql_delete_task = "DELETE FROM tasks WHERE task_id = $task_id_to_delete";
        if ($conn->query($sql_delete_task) === TRUE) {
            echo '<p>Tâche supprimée avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression de la tâche : ' . $conn->error . '</p>';
        }
    } else {
        echo '<p>Données de suppression invalides.</p>';
    }
}


// UPDATE Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $task_id_to_update = $_POST['edit_task_id'];
    $updated_name = htmlspecialchars($_POST['edit_name']);

    // Validation des données (à adapter selon vos besoins)
    if (!empty($task_id_to_update) && is_numeric($task_id_to_update) && !empty($updated_name)) {
        $sql_update_task = "UPDATE tasks SET name = '$updated_name' WHERE task_id = $task_id_to_update";

        if ($conn->query($sql_update_task) === TRUE) {
            echo '<p>Tâche mise à jour avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la mise à jour de la tâche : ' . $conn->error . '</p>';
        }
    } else {
        echo '<p>Données de mise à jour invalides.</p>';
    }
}

// READ Tasks
if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
    $project_id = $_GET['project_id'];

    $sql_read_tasks = "SELECT * FROM tasks WHERE project_id = $project_id";
    $result_tasks = $conn->query($sql_read_tasks);

    if (!$result_tasks) {
        echo '<p>Erreur lors de la récupération des tâches : ' . $conn->error . '</p>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/project.css">
    <title>Gestion des Tâches</title>
</head>

<body>
    <main>
        <!-- Bouton pour afficher le formulaire d'ajout dans un modal -->
        <button onclick="openAddTaskModal()">Ajouter une Tâche</button>

        <!-- Modal d'ajout de tâche -->
        <div id="addTaskModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddTaskModal()">&times;</span>
                <h2>Ajouter une Tâche</h2>
                <form method="POST" action="">
                    <label for="name">Nom de la Tâche:</label>
                    <input type="text" name="name" required>

                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                    <button type="submit" name="create_task">Ajouter</button>
                </form>
            </div>
        </div>

        <!-- Modal d'édition de tâche -->
        <div id="editTaskModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditTaskModal()">&times;</span>
                <h2>Modifier la Tâche</h2>
                <form method="POST" action="">
                    <input type="hidden" id="edit_task_id" name="edit_task_id" value="">
                    <label for="edit_name">Nom de la Tâche:</label>
                    <input type="text" id="edit_name" name="edit_name" required>

                    <button type="submit" name="update_task">Mettre à Jour</button>
                </form>
            </div>
        </div>

        <!-- Tableau pour afficher la liste des tâches -->
        <h2>Liste des Tâches</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($result_tasks) && $result_tasks->num_rows > 0) {
                    while ($row_task = $result_tasks->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row_task['name'] . '</td>';
                        echo '<td>' . $row_task['status'] . '</td>';
                        echo '<td>';
                        echo '<button onclick="openEditTaskModal(' . $row_task['task_id'] . ', \'' . $row_task['name'] . '\')">Edit</button>';
                        echo '<button onclick="confirmDelete(' . $row_task['task_id'] . ')">Delete</button>';
                        echo '<a href="membre.php?project_id=' . $row_task['project_id'] . '&task_id=' . $row_task['task_id'] . '"><button>Membre</button></a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">Aucune tâche trouvée pour ce projet.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </main>

    <!-- Script pour gérer les modals et la suppression -->
    <script>
        function openAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'block';
        }

        function closeAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'none';
        }

        function openEditTaskModal(taskId, name) {
            document.getElementById('edit_task_id').value = taskId;
            document.getElementById('edit_name').value = name;
            document.getElementById('editTaskModal').style.display = 'block';
        }

        function closeEditTaskModal() {
            document.getElementById('editTaskModal').style.display = 'none';
        }

        function confirmDelete(taskId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
                document.getElementById('delete_task_form').task_id_to_delete.value = taskId;
                document.getElementById('delete_task_form').submit();
            }
        }
    </script>

    <!-- Formulaire caché pour la suppression de tâche -->
    <form id="delete_task_form" method="POST" action="">
        <input type="hidden" name="delete_task" value="1">
        <input type="hidden" name="task_id_to_delete" value="">
    </form>

    <?php include('footer.php'); ?>
</body>

</html>