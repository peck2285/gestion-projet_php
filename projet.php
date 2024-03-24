<?php
include('header.php');
include('config.php');

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $name = htmlspecialchars($_POST['name']);
    $description = htmlspecialchars($_POST['description']);

    // Validation des données (à adapter selon vos besoins)
    if (empty($name) || empty($description)) {
        echo '<p>Veuillez remplir tous les champs.</p>';
    } else {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

        // Vérifier si le nom du projet existe déjà
        $sql_check_duplicate = "SELECT COUNT(*) as count FROM projects WHERE name = ?";
        $stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
        $stmt_check_duplicate->bind_param("s", $name);
        $stmt_check_duplicate->execute();
        $result_check_duplicate = $stmt_check_duplicate->get_result();
        $row_check_duplicate = $result_check_duplicate->fetch_assoc();

        if ($row_check_duplicate['count'] > 0) {
            echo '<p>Le nom du projet existe déjà. Veuillez choisir un nom unique.</p>';
        } else {
            // Utilisation d'une requête préparée pour éviter l'injection SQL
            $sql_create_project = $conn->prepare("INSERT INTO projects (name, description, user_id) VALUES (?, ?, ?)");
            $sql_create_project->bind_param("ssi", $name, $description, $user_id);

            if ($sql_create_project->execute()) {
                echo '<p>Projet ajouté avec succès.</p>';
            } else {
                echo '<p>Erreur lors de l\'ajout du projet : ' . $conn->error . '</p>';
            }

            $sql_create_project->close();
        }

        $stmt_check_duplicate->close();
    }
}


// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_id_to_delete = $_POST['project_id_to_delete'];

    // Validation des données (à adapter selon vos besoins)
    if (!empty($project_id_to_delete) && is_numeric($project_id_to_delete)) {
        // Supprimer les membres liés au projet
        $sql_delete_members = "DELETE FROM members WHERE project_id = $project_id_to_delete";
        if ($conn->query($sql_delete_members) === TRUE) {
            // echo '<p>Membres du projet supprimés avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression des membres du projet : ' . $conn->error . '</p>';
        }

        // Supprimer les tâches liées au projet
        $sql_delete_tasks = "DELETE FROM tasks WHERE project_id = $project_id_to_delete";
        if ($conn->query($sql_delete_tasks) === TRUE) {
            // echo '<p>Tâches du projet supprimées avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression des tâches du projet : ' . $conn->error . '</p>';
        }

        // Enfin, supprimer le projet lui-même
        $sql_delete_project = "DELETE FROM projects WHERE project_id = $project_id_to_delete";
        if ($conn->query($sql_delete_project) === TRUE) {
            echo '<p>Projet supprimé avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la suppression du projet : ' . $conn->error . '</p>';
        }
    } else {
        echo '<p>Données de suppression invalides.</p>';
    }
}


// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id_to_update = $_POST['edit_project_id'];
    $updated_name = htmlspecialchars($_POST['edit_name']);
    $updated_description = htmlspecialchars($_POST['edit_description']);

    // Validation des données (à adapter selon vos besoins)
    if (!empty($project_id_to_update) && is_numeric($project_id_to_update) && !empty($updated_name) && !empty($updated_description)) {
        $sql_update_project = "UPDATE projects SET name = '$updated_name', description = '$updated_description' WHERE project_id = $project_id_to_update";

        if ($conn->query($sql_update_project) === TRUE) {
            echo '<p>Projet mis à jour avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la mise à jour du projet : ' . $conn->error . '</p>';
        }
    } else {
        echo '<p>Données de mise à jour invalides.</p>';
    }
}

// READ
$user_loged = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$sql_read_projects = "SELECT p.* 
FROM projects p
LEFT JOIN members m ON p.project_id = m.project_id
WHERE p.user_id = $user_loged OR m.user_id = $user_loged";
$result_projects = $conn->query($sql_read_projects);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/project.css">
    <title>Gestion de Projets</title>
</head>

<body>
    <main>
        <!-- Bouton pour afficher le formulaire d'ajout dans un modal -->
        <button onclick="openAddModal()">Ajouter</button>

        <!-- Modal d'ajout de projet -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">&times;</span>
                <h2>Ajouter un Projet</h2>
                <form method="POST" action="">
                    <label for="name">Nom du Projet:</label>
                    <input type="text" name="name" required>

                    <label for="description">Description:</label>
                    <textarea name="description" required></textarea>

                    <button type="submit" name="create_project">Ajouter</button>
                </form>
            </div>
        </div>

        <!-- Modal d'édition de projet -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>Modifier le Projet</h2>
                <form method="POST" action="">
                    <input type="hidden" id="edit_project_id" name="edit_project_id" value="">
                    <label for="edit_name">Nom du Projet:</label>
                    <input type="text" id="edit_name" name="edit_name" required>

                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="edit_description" required></textarea>

                    <button type="submit" name="update_project">Mettre à Jour</button>
                </form>
            </div>
        </div>

        <!-- Tableau pour afficher la liste des projets -->
        <h2>Liste des Projets</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_projects->num_rows > 0) {
                    while ($row_project = $result_projects->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row_project['name'] . '</td>';
                        echo '<td>' . $row_project['description'] . '</td>';
                        echo '<td>';
                        echo '<button onclick="openEditModal(' . $row_project['project_id'] . ', \'' . $row_project['name'] . '\', \'' . $row_project['description'] . '\')">Edit</button>';
                        echo '<button onclick="confirmDelete(' . $row_project['project_id'] . ')">Delete</button>';
                        echo '<a href="taches.php?project_id=' . $row_project['project_id'] . '"><button>Tâches</button></a>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">Aucun projet trouvé.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </main>

    <!-- Script pour gérer les modals et la suppression -->
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(projectId, name, description) {
            document.getElementById('edit_project_id').value = projectId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(projectId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
                document.getElementById('delete_project_form').project_id_to_delete.value = projectId;
                document.getElementById('delete_project_form').submit();
            }
        }
    </script>

    <!-- Formulaire caché pour la suppression de projet -->
    <form id="delete_project_form" method="POST" action="">
        <input type="hidden" name="delete_project" value="1">
        <input type="hidden" name="project_id_to_delete" value="">
    </form>

    <?php include('footer.php'); ?>
</body>

</html>