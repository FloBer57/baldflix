<?php
session_start();

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers la page de connexion
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: baldflix_login.php");
  exit;
}

// Inclure le fichier de configuration
require_once "config.php";

// Vérifier si l'utilisateur connecté est un administrateur
if ($_SESSION["user_role_id"] != 2) {
  header("location: profile.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST["reset_password"])) {
    include_once "admin_reset_password.php";
  } elseif (isset($_POST["modify"])) {
    include_once "admin_role.php";
  }
  // Vous pouvez ajouter d'autres conditions ici si nécessaire
} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["user_id"])) {
    include_once "admin_delete.php";
  }
  // Ajoutez d'autres conditions GET ici si nécessaire
}

$_SESSION["csrf_token"] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="robots" content="noindex, nofollow, noimageindex">
  <title>Administration</title>
  <link rel="shortcut icon" href="../image/favicon/bald_favicon.ico" type="image/x-icon" />
  <link href="../css/global.CSS" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Bungee+Inline&display=swap" rel="stylesheet">
</head>

<body class="background bodyburger">
  <?php require_once "../includes/header.php";
  ?>

  <main>
    <div class="account__container">
      <div class="sub__container admin__container">
        <nav class="account__nav">
          <ul class="admin__nav">
            <li data-tab="admin-user-tab-content" onclick="showTab('admin-user-tab-content')">
              Administration des utilisateurs</li>
            <li data-tab="admin-video-tab-content" onclick="showTab('admin-video-tab-content')">Administration des
              vidéos</li>
          </ul>
        </nav>
        <div id="admin-user-tab-content" class="tab__content admin__content">
          <h2>Administration des utilisateurs</h2>
          <?php
          // Requête SQL pour récupérer tous les utilisateurs
          $sql = "SELECT user_id, username, user_role_id FROM user";
          $result = mysqli_query($link, $sql);

          if ($result) {
            echo "<table>";
            echo "<tr><th>Nom</th><th>Role</th><th>Action</th><th>MDP</th><th>Supprimer</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
              echo "<tr>";
              echo "<td>" . htmlspecialchars($row['username']) . "</td>";
              echo "<td>" . htmlspecialchars($row['user_role_id']) . "</td>";

              // Formulaire pour modifier le statut
              echo "<td>
              <form method='post' action=''>
                <input type='hidden' name='csrf_token' value='{$_SESSION["csrf_token"]}'>
                <input type='hidden' name='user_id' value='{$row['user_id']}'>
                <select name='new_role'>
                    <option value='1' " . ($row['user_role_id'] == '1' ? 'selected' : '') . ">User</option>
                    <option value='2' " . ($row['user_role_id'] == '2' ? 'selected' : '') . ">Admin</option>
                </select>
                <input type='submit' name='modify' value='Modifier'>
              </form>
            </td>";

              // Formulaire pour réinitialiser le mot de passe
              echo "<td>
        <form method='post' action=''>
          <input type='hidden' name='csrf_token' value='{$_SESSION["csrf_token"]}'>
          <input type='hidden' name='user_id' value='{$row['user_id']}'>
          <input type='submit' name='reset_password' value='Réinitialiser'>
        </form>
      </td>";

              // Lien pour supprimer l'utilisateur
              echo "<td>
        <a href='#' onclick='confirmDelete(\"?action=delete&user_id={$row['user_id']}&csrf_token={$_SESSION["csrf_token"]}\")'>
          <img src='../image/icon/delete.svg' alt='Supprimer' title='Supprimer'>
        </a>
      </td>";

              echo "</tr>";
            }
            echo "</table>";
          } else {
            echo "Erreur de requête : " . mysqli_error($link);
          }
          ?>
        </div>
        <div id="admin-video-tab-content" class="tab__content admin__content active-tab">
          <h2>Administration des vidéos</h2>
          <form method="post" enctype="multipart/form-data">
            <!-- Catégorie -->
            <label for="category">Catégorie:</label>
            <select id="category" name="category" onchange="toggleFields()">
              <option value="anime">Anime/Série/Emission TV</option>
              <option value="film">Film</option>
            </select><br><br>

            <!-- Champs communs -->
            <label for="title">Titre:</label>
            <input type="text" id="title" name="title"><br><br>
            <label for="trailer">Trailer (URL):</label>
            <input type="text" id="trailer" name="trailer"><br><br>
            <label for="synopsis">Synopsis:</label>
            <textarea id="synopsis" name="synopsis"></textarea><br><br>
            <label for="image">Image de présentation:</label>
            <input type="file" id="image" name="image"><br><br>

            <!-- Champs spécifiques à Anime/Série/Emission TV -->
            <div id="series-fields" style="display:none;">
              <label for="season">Saison:</label>
              <input type="number" id="season" name="season">
              <label for="episode">Épisode:</label>
              <input type="number" id="episode" name="episode"><br><br>
            </div>

            <!-- Champs spécifiques au Film -->
            <div id="film-fields" style="display:none;">
              <label for="video">Vidéo:</label>
              <input type="file" id="video" name="video"><br><br>
            </div>

            <!-- Bouton de soumission -->
            <input type="submit" value="Sauvegarder">
          </form>
        </div>
        <script>
          function toggleFields() {
            var category = document.getElementById('category').value;
            document.getElementById('series-fields').style.display = (category == 'anime') ? 'block' : 'none';
            document.getElementById('film-fields').style.display = (category == 'film') ? 'block' : 'none';
          }
          window.onload = toggleFields; // Pour initialiser les champs lors du chargement de la page
        </script>
        <script src="../js/account.js"></script>
        <script src="../js/burger.js"></script>
      </div>
    </div>
  </main>
</body>

</html>