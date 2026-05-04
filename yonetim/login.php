<?php
/**
 * AKSOY GROUP — Admin Login
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    redirect('/yonetim/');
}

$error = '';
$next = $_GET['next'] ?? '/yonetim/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $result = Auth::attempt($username, $password);
        if ($result['ok']) {
            redirect($next);
        }
        $error = $result['error'] ?? 'Giriş başarısız.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Yönetim Paneli — <?= h(setting('site_title','AKSOY GROUP')) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@200;300;400;500&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Manrope',sans-serif; background:#0A0E1A; color:#F5F1E8; min-height:100vh;
    display:flex; align-items:center; justify-content:center; padding:40px 20px;
    background-image:
        radial-gradient(ellipse at 30% 20%, rgba(201,169,97,.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(139,0,0,.05) 0%, transparent 50%);
}
.split { display:grid; grid-template-columns:1fr 1fr; gap:0; max-width:980px; width:100%; min-height:560px;
    border:1px solid #2A2E3A; border-radius:14px; overflow:hidden; background:#0F1424; box-shadow:0 40px 100px -30px rgba(0,0,0,.6); }
.brand-side {
    padding:60px 50px; display:flex; flex-direction:column; justify-content:space-between;
    background-image:
        linear-gradient(180deg, rgba(10,14,26,.4), rgba(10,14,26,.95)),
        radial-gradient(circle at 20% 30%, rgba(201,169,97,.15), transparent 60%);
    border-right:1px solid #2A2E3A; position:relative;
}
.brand-side::before {
    content:''; position:absolute; top:30px; left:30px; right:30px;
    border-top:1px solid rgba(201,169,97,.15);
}
.brand-side::after {
    content:''; position:absolute; bottom:30px; left:30px; right:30px;
    border-bottom:1px solid rgba(201,169,97,.15);
}
.logo { font-family:'Fraunces',serif; font-weight:200; font-size:54px; letter-spacing:.14em; color:#C9A961; line-height:1; }
.tag { font-size:11px; letter-spacing:.25em; text-transform:uppercase; color:rgba(245,241,232,.55); margin-top:8px; }
.poetry {
    margin-top:auto; font-family:'Fraunces',serif; font-weight:300; font-size:18px; line-height:1.6;
    color:rgba(245,241,232,.85); font-style:italic;
}
.poetry::before { content:'\201C'; font-size:48px; color:#C9A961; line-height:0; vertical-align:-12px; margin-right:6px; }
.poetry .author { display:block; font-style:normal; font-size:11px; letter-spacing:.2em; text-transform:uppercase; color:#C9A961; margin-top:14px; }
.form-side { padding:60px 50px; display:flex; flex-direction:column; justify-content:center; }
.form-side h1 { font-family:'Fraunces',serif; font-weight:300; font-size:32px; letter-spacing:.01em; margin-bottom:6px; }
.form-side .subtitle { font-size:13px; color:#8c8a82; margin-bottom:36px; }
.field { margin-bottom:18px; }
.field label { display:block; font-size:10px; letter-spacing:.2em; color:#C9A961; text-transform:uppercase; margin-bottom:8px; font-weight:600; }
.field input {
    width:100%; padding:13px 16px; background:#0A0E1A; border:1px solid #2A2E3A; color:#F5F1E8;
    border-radius:6px; font-family:inherit; font-size:14px; transition:all .2s;
}
.field input:focus { outline:none; border-color:#C9A961; background:#0F1428; box-shadow:0 0 0 3px rgba(201,169,97,.1); }
button {
    width:100%; padding:15px; background:#C9A961; color:#0A0E1A; border:none; border-radius:6px;
    font-family:'Fraunces',serif; font-size:14px; font-weight:400; letter-spacing:.18em;
    text-transform:uppercase; cursor:pointer; transition:all .2s; margin-top:10px;
}
button:hover { background:#d6b870; transform:translateY(-1px); }
.alert { padding:13px 16px; border-radius:6px; margin-bottom:18px; font-size:13px;
    background:#2a1414; color:#ff9999; border-left:3px solid #c83a3a; }
.foot { margin-top:30px; font-size:11px; color:#4c4a42; letter-spacing:.1em; text-align:center; }
.foot a { color:#8c8a82; text-decoration:none; }
.foot a:hover { color:#C9A961; }
@media (max-width:768px) { .split { grid-template-columns:1fr; } .brand-side { padding:40px 30px; min-height:240px; } .form-side { padding:40px 30px; } }
</style>
</head>
<body>
<div class="split">
    <div class="brand-side">
        <div>
            <div class="logo">AKSOY</div>
            <div class="tag">Group · Yönetim Paneli</div>
        </div>
        <div class="poetry">
            Geleceği inşa edenler, geçmişi unutmazlar.
            <span class="author">— Aksoy Group Vizyonu</span>
        </div>
    </div>
    <div class="form-side">
        <h1>Hoş geldiniz</h1>
        <p class="subtitle">Yönetim panelinize giriş yapın</p>

        <?php if ($error): ?>
            <div class="alert">⚠ <?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
            <?= CSRF::field() ?>
            <div class="field">
                <label>Kullanıcı Adı / E-posta</label>
                <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="field">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>

        <div class="foot">
            <a href="/">← Ana sayfaya dön</a>
        </div>
    </div>
</div>
</body>
</html>
