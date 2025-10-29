<?php
// ...existing code...
<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Home - COS</title>
<style>
body{font-family:Segoe UI,Roboto,Arial,sans-serif;margin:0;color:#222;background:#f7f7f8}
.header{background:#2b6cb0;color:#fff;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between}
.brand{font-weight:700;font-size:1.1rem}
.nav a{color:#fff;margin-left:1rem;text-decoration:none}
.container{max-width:1000px;margin:2rem auto;padding:0 1rem}
.card{background:#fff;padding:1.25rem;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem}
.footer{padding:1rem;text-align:center;color:#666;font-size:.9rem;margin-top:2rem}
button.cta{background:#2b6cb0;color:#fff;border:none;padding:.6rem .9rem;border-radius:6px;cursor:pointer}
@media(prefers-color-scheme:dark){
  body{background:#0b0f13;color:#dbeafe}
  .card{background:#071022;box-shadow:none}
  .header{background:#0a3353}
}
</style>
</head>
<body>
<header class="header">
  <div class="brand">COS</div>
  <nav class="nav" aria-label="Main navigation">
    <a href="Homepage.php">Home</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <?php if($user): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Sign out</a>
    <?php else: ?>
      <a href="login.php">Sign in</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container" role="main">
  <section class="card">
    <h1>Welcome<?php if($user) echo ', '.htmlspecialchars($user['name'] ?? $user); ?>.</h1>
    <p>Centralized operations site. Use the links above to navigate.</p>
    <div style="margin-top:1rem">
      <?php if(!$user): ?>
        <a class="cta" href="login.php">Sign in</a>
      <?php else: ?>
        <a class="cta" href="dashboard.php">Go to dashboard</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="grid" aria-label="Quick actions">
    <div class="card">
      <h3>Recent activity</h3>
      <p id="recent">No activity to show.</p>
    </div>
    <div class="card">
      <h3>Quick links</h3>
      <ul>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="projects.php">Projects</a></li>
        <li><a href="reports.php">Reports</a></li>
      </ul>
    </div>
    <div class="card">
      <h3>Search</h3>
      <form method="get" action="search.php" role="search">
        <label for="q" class="sr-only">Search</label>
        <input id="q" name="q" type="search" placeholder="Search..." style="width:100%;padding:.5rem;border-radius:6px;border:1px solid #d1d5db">
      </form>
    </div>
  </section>

  <section class="card" style="margin-top:1rem">
    <h3>Getting started</h3>
    <ol>
      <li>Sign in or create an account.</li>
      <li>Configure settings.</li>
      <li>Create or join a project.</li>
    </ol>
  </section>

  <div class="footer">
    &copy; <?php echo date('Y'); ?> COS — built with PHP. <small style="display:block;margin-top:.5rem">This is a scaffolded homepage; customize as needed.</small>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var recent = document.getElementById('recent');
  setTimeout(function(){ recent.textContent = 'No recent events.' }, 300);
});
</script>
</body>
</html>
// ...existing code...
```// filepath: c:\Users\Admin\OneDrive\Documents\GitHub\cos\Homepage.php
// ...existing code...
<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Home - COS</title>
<style>
body{font-family:Segoe UI,Roboto,Arial,sans-serif;margin:0;color:#222;background:#f7f7f8}
.header{background:#2b6cb0;color:#fff;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between}
.brand{font-weight:700;font-size:1.1rem}
.nav a{color:#fff;margin-left:1rem;text-decoration:none}
.container{max-width:1000px;margin:2rem auto;padding:0 1rem}
.card{background:#fff;padding:1.25rem;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem}
.footer{padding:1rem;text-align:center;color:#666;font-size:.9rem;margin-top:2rem}
button.cta{background:#2b6cb0;color:#fff;border:none;padding:.6rem .9rem;border-radius:6px;cursor:pointer}
@media(prefers-color-scheme:dark){
  body{background:#0b0f13;color:#dbeafe}
  .card{background:#071022;box-shadow:none}
  .header{background:#0a3353}
}
</style>
</head>
<body>
<header class="header">
  <div class="brand">COS</div>
  <nav class="nav" aria-label="Main navigation">
    <a href="Homepage.php">Home</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <?php if($user): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Sign out</a>
    <?php else: ?>
      <a href="login.php">Sign in</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container" role="main">
  <section class="card">
    <h1>Welcome<?php if($user) echo ', '.htmlspecialchars($user['name'] ?? $user); ?>.</h1>
    <p>Centralized operations site. Use the links above to navigate.</p>
    <div style="margin-top:1rem">
      <?php if(!$user): ?>
        <a class="cta" href="login.php">Sign in</a>
      <?php else: ?>
        <a class="cta" href="dashboard.php">Go to dashboard</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="grid" aria-label="Quick actions">
    <div class="card">
      <h3>Recent activity</h3>
      <p id="recent">No activity to show.</p>
    </div>
    <div class="card">
      <h3>Quick links</h3>
      <ul>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="projects.php">Projects</a></li>
        <li><a href="reports.php">Reports</a></li>
      </ul>
    </div>
    <div class="card">
      <h3>Search</h3>
      <form method="get" action="search.php" role="search">
        <label for="q" class="sr-only">Search</label>
        <input id="q" name="q" type="search" placeholder="Search..." style="width:100%;padding:.5rem;border-radius:6px;border:1px solid #d1d5db">
      </form>
    </div>
  </section>

  <section class="card" style="margin-top:1rem">
    <h3>Getting started</h3>
    <ol>
      <li>Sign in or create an account.</li>
      <li>Configure settings.</li>
      <li>Create or join a project.</li>
    </ol>
  </section>

  <div class="footer">
    &copy; <?php echo date('Y'); ?> COS — built with PHP. <small style="display:block;margin-top:.5rem">This is a scaffolded homepage; customize as needed.</small>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var recent = document.getElementById('recent');
  setTimeout(function(){ recent.textContent = 'No recent events.' }, 300);
});
</script>
</body>
</html>
// ...existing code...