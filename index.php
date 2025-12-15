<?php
session_start();

// DATABASE CONNECTION
$host = "localhost";
$user = "root";  
$pass = "";     
$db   = "web";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// HANDLE REGISTER
$register_msg = $login_msg = "";
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $company = $_POST['company_name'] ?? 'N/A'; // Save company from form or default

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $register_msg = "Email already registered!";
    } else {
       $sql = "INSERT INTO users (name, email, password, role, company) 
        VALUES ('$name','$email','$password','Admin','$company')";
        if ($conn->query($sql)) {
             
            // Don't log in automatically, just show message
            $register_msg = "Registration successful! Please login to continue.";
        } else {
            $register_msg = "Error: " . $conn->error;
        }
    }
}


// HANDLE LOGIN
// HANDLE LOGIN
if (isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = trim($_POST['login_password']);

    // First check in adminlist (for Admins and Users created by admin)
    $stmt = $conn->prepare("SELECT * FROM adminlist WHERE email = ? AND status = 'Active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
                $_SESSION['company'] = $row['company'] ;
    $_SESSION['gender'] = $row['gender'] ?? 'female';


            // Redirect based on role
            if ($row['role'] === 'Admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $login_msg = "Incorrect password!";
        }
    } else {
        // If not in adminlist, check in users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['company'] = $row['company'] ;
$_SESSION['gender'] = $row['gender'] ?? 'female'; 
                // Redirect based on role
                if ($row['role'] === 'Admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $login_msg = "Incorrect password!";
            }
        } else {
            $login_msg = "No account found!";
        }
    }
}


// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login & Register</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { display:flex; justify-content:center; align-items:center; height:100vh; background-color:#140d77; }

.container {
  width:800px;
  height:500px;
  background:#fff;
  border-radius:20px;
  box-shadow:0 10px 25px rgba(0,0,0,0.2);
  display:flex;
  overflow:hidden;
}

.left {
  flex:1;
  background:#140d77;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  color:#fff;
  padding:20px;
}

.left h1 {
  font-size:40px;
  margin-bottom:10px;
}

.left p {
  font-size:16px;
  margin-bottom:20px;
  text-align:center;
  line-height:1.5;
}

.left button {
  background:#fff;
  color:#140d77;
  border:none;
  padding:10px 30px;
  border-radius:25px;
  font-weight:bold;
  cursor:pointer;
  transition:0.3s;
}

.left button:hover { background:#e5e5ff; }

.right {
  flex:1;
  display:flex;
  justify-content:center;
  align-items:center;
  flex-direction:column;
  padding:40px;
}

form {
  display:flex;
  flex-direction:column;
  width:80%;
}

form h2 {
  text-align:center;
  color:#140d77;
  margin-bottom:20px;
}

input {
  margin:10px 0;
  padding:12px;
  border:none;
  border-bottom:2px solid #ccc;
  outline:none;
  font-size:16px;
  transition:0.3s;
}

input:focus { border-color:#140d77; }

button.submit {
  background:#140d77;
  color:#fff;
  border:none;
  padding:12px;
  border-radius:25px;
  font-size:16px;
  cursor:pointer;
  margin-top:10px;
  transition:0.3s;
}

button.submit:hover { background:#0e085c; }

.message {
  color:#e74c3c;
  text-align:center;
  margin-top:10px;
  font-size:14px;
}

a { color:#140d77; text-decoration:none; }
a:hover { text-decoration:underline; }

.dashboard { text-align:center; }

/* ✅ Logo styling */
.logo-container {
  width:120px;
  height:120px;
  margin-bottom:20px;
  border-radius:50%;
  background-image:url('images/Sts_logo-6.png'); /* <-- change to your actual image name/path */
  background-repeat:no-repeat;
  background-size:contain;
  background-position:center;
}
</style>

<script>
function showForm(type){
  document.getElementById('loginForm').style.display = (type==='login')?'flex':'none';
  document.getElementById('registerForm').style.display = (type==='register')?'flex':'none';
}
</script>
</head>
<body>
<?php if(isset($_SESSION['user'])): ?>
  <div class="container" style="justify-content:center;align-items:center;">
    <div class="dashboard">
      <h2>Welcome, <?php echo $_SESSION['user']; ?> 🎉</h2>
      <p>Role: <?php echo $_SESSION['role']; ?></p>
      <p>Company: <?php echo $_SESSION['company'];?></p>
      <a href="?logout=1">Logout</a>
    </div>
  </div>
<?php else: ?>
  <div class="container">
    <div class="left">
       <div class="logo-container"></div>
      <h1>Welcome!</h1>
      <p>Register first, then login to continue</p>
      <button onclick="showForm('login')">Login</button>
      <button onclick="showForm('register')" style="margin-top:10px;">Register</button>
    </div>
    <div class="right">
      <form id="loginForm" method="POST" style="display:none;">
        <h2>Login</h2>
        <input type="email" name="login_email" placeholder="Email" required>
        <input type="password" name="login_password" placeholder="Password" required>
        <button type="submit" name="login" class="submit">LOGIN</button>
        <p class="message"><?php echo $login_msg; ?></p>
      </form>
      <form id="registerForm" method="POST">
        <h2>Register</h2>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
         <input type="text" name="company_name" placeholder="Company Name ">
        <button type="submit" name="register" class="submit">REGISTER</button>
        <p class="message"><?php echo $register_msg; ?></p>
      </form>
    </div>
  </div>
<?php endif; ?>
<script>
// Show login form by default when page loads
window.onload = function() {
  document.getElementById('loginForm').style.display = 'flex';
  document.getElementById('registerForm').style.display = 'none';
};

// Keep your existing showForm function
function showForm(type) {
  document.getElementById('loginForm').style.display = (type === 'login') ? 'flex' : 'none';
  document.getElementById('registerForm').style.display = (type === 'register') ? 'flex' : 'none';
}
</script>

</body>
</html>
