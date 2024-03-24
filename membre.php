<?php
include('header.php');
include('config.php');

// CREATE Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $project_id = $_POST['project_id'];
    $task_id = $_POST['task_id'];
    $user_id = $_POST['user_id'];

    if (empty($user_id)) {
        echo '<p>Veuillez choisir un membre.</p>';
    } else {
        // Vérifier si la tâche existe déjà
        $sql_check_task = $conn->prepare("SELECT * FROM members WHERE user_id = ? AND task_id = ?");
        $sql_check_task->bind_param("is", $user_id, $task_id);
        $sql_check_task->execute();
        $result_check_task = $sql_check_task->get_result();

        if ($result_check_task->num_rows > 0) {
            echo '<p>L\'utilisateur existe déjà.</p>';
        } else {
            // Utilisation d'une requête préparée pour éviter l'injection SQL
            $sql_create_task = $conn->prepare("INSERT INTO members (task_id, project_id, user_id) VALUES (?, ?, ?)");
            $sql_create_task->bind_param("iis", $task_id, $project_id, $user_id);

            if ($sql_create_task->execute()) {
                echo '<p>Membre ajouté avec succès.</p>';
            } else {
                echo '<p>Erreur lors de l\'ajout du membre : ' . $conn->error . '</p>';
            }

            $sql_create_task->close();
        }

        $sql_check_task->close();
    }
}

// DELETE Members
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id_to_delete = $_POST['task_id_to_delete'];

    // Validation des données (à adapter selon vos besoins)
    if (!empty($task_id_to_delete) && is_numeric($task_id_to_delete)) {
        $sql_delete_task = $conn->prepare("DELETE FROM members WHERE member_id = ?");
        $sql_delete_task->bind_param("i", $task_id_to_delete); // Correction : Utiliser "i" au lieu de "s"

        if ($sql_delete_task->execute()) {
            echo '<p>Utilisateur supprimé avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression de l\'utilisateur : ' . $conn->error . '</p>';
        }

        $sql_delete_task->close();
    } else {
        echo '<p>Données de suppression invalides.</p>';
    }
}

// READ Members
if (isset($_GET['project_id']) && isset($_GET['task_id']) && is_numeric($_GET['project_id']) && is_numeric($_GET['task_id'])) {
    $project_id = $_GET['project_id'];
    $task_id = $_GET['task_id'];

    // Utiliser une jointure pour obtenir les informations de l'utilisateur associé à la tâche
    $sql_read_members = "
        SELECT members.*, users.username
        FROM members
        INNER JOIN users ON members.user_id = users.user_id
        WHERE members.project_id = $project_id AND members.task_id = $task_id
    ";

    $result_members = $conn->query($sql_read_members);

    if (!$result_members) {
        echo '<p>Erreur lors de la récupération des membres : ' . $conn->error . '</p>';
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/project.css">
    <title>Gestion des Membres</title>
    <!-- Script pour gérer les modals et la suppression -->
    <script>
        function openAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'flex';
        }

        function closeAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'none';
        }

        function confirmDelete(memberId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce membre ?')) {
                document.getElementById('delete_task_form').task_id_to_delete.value = memberId;
                document.getElementById('delete_task_form').submit();
            }
        }
    </script>
</head>

<body>
    <main>
        <!-- Bouton pour afficher le formulaire d'ajout dans un modal -->
        <button onclick="openAddTaskModal()">Ajouter un Membre</button>

        <!-- Modal d'ajout de tâche -->
        <div id="addTaskModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddTaskModal()">&times;</span>
                <form method="POST" action="">
                    <label for="name">Nom de la Tâche:</label>
                    <select name="user_id">
                        <?php
                        // Assurez-vous de valider et de nettoyer les données entrantes avant de les utiliser dans la requête SQL
                        $user_loged = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                        $sql_users = "SELECT user_id, username FROM users WHERE user_id != $user_loged";
                        $result_users = $conn->query($sql_users);

                        if ($result_users->num_rows > 0) {
                            while ($row_user = $result_users->fetch_assoc()) {
                                echo '<option value="' . $row_user['user_id'] . '">' . $row_user['username'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

                    <button type="submit" name="create_task">Ajouter</button>
                </form>
            </div>
        </div>

        <!-- Tableau pour afficher la liste des membres -->
        <h2>Liste des Membres</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($result_members) && $result_members->num_rows > 0) {
                    while ($row_member = $result_members->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row_member['username'] . '</td>';
                        echo '<td>';
                        echo '<button onclick="confirmDelete(' . $row_member['member_id'] . ')">Delete</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">Aucun membre trouvé pour cette tâche.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </main>



    <!-- Formulaire caché pour la suppression de tâche -->
    <form id="delete_task_form" method="POST" action="">
        <input type="hidden" name="delete_task" value="1">
        <input type="hidden" name="task_id_to_delete" value="">
    </form>

    <?php include('footer.php'); ?>
</body>

</html>