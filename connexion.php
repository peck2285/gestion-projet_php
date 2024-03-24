<?php
require('config.php');

// Initialisation des variables pour éviter des erreurs
$email = $password = '';
$errors = array();

// Vérification si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validation des champs
    if (empty($email)) {
        $errors[] = "L'adresse email est requise.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    }

    // S'il n'y a pas d'erreurs, procéder à la connexion
    if (empty($errors)) {
        // Recherche de l'utilisateur dans la base de données
        $sql = "SELECT user_id, username, email, password FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Vérification du mot de passe
            if (password_verify($password, $row['password'])) {
                // Démarrez la session et redirigez vers une page de succès ou du tableau de bord
                session_start();
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                header("Location: projet.php");
                exit();
            } else {
                $errors[] = "Mot de passe incorrect.";
            }
        } else {
            $errors[] = "Aucun utilisateur trouvé avec cette adresse email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/connexion.css">
    <title>Connexion</title>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2>Connexion</h2>

            <?php
            // Afficher les erreurs s'il y en a
            if (!empty($errors)) {
                echo '<div class="error-container">';
                foreach ($errors as $error) {
                    echo '<p class="error-message">' . $error . '</p>';
                }
                echo '</div>';
            }
            ?>

            <form id="login-form" method="POST" action="">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Se connecter</button>
            </form>
            <p>Vous n'avez pas de compte? <a href="inscription.php">Inscrivez-vous ici</a>.</p>
        </div>
    </div>
</body>

</html>