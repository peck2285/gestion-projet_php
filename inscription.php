<?php
require('config.php');

// Initialisation des variables pour éviter des erreurs
$username = $email = $password = '';
$errors = array();

// Vérification si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validation des champs
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis.";
    }

    if (empty($email)) {
        $errors[] = "L'adresse email est requise.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    }

    // S'il n'y a pas d'erreurs, procéder à l'inscription
    if (empty($errors)) {
        // Hashage du mot de passe avant de le stocker dans la base de données
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertion des données dans la base de données
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            // Rediriger vers une page de succès ou de connexion
            header("Location: connexion.php");
            exit();
        } else {
            $errors[] = "Erreur lors de l'inscription : " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/inscription.css">
    <title>Inscription</title>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2>Inscription</h2>

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

            <form id="signup-form" method="POST" action="">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">S'inscrire</button>
            </form>
        </div>
    </div>
</body>

</html>