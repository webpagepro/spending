
<?php
try {
  $db = new PDO('sqlite:database.sqlite');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  // Create tables
  $db->exec("
    CREATE TABLE IF NOT EXISTS income (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      source TEXT,
      amount DECIMAL(10,2),
      date DATE
    );
    
    CREATE TABLE IF NOT EXISTS expenses (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category TEXT,
      description TEXT,
      amount DECIMAL(10,2),
      date DATE
    );
  ");

  // Handle form submissions
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_income'])) {
      $stmt = $db->prepare("DELETE FROM income WHERE id = :id");
      $stmt->execute([':id' => $_POST['id']]);
    } elseif (isset($_POST['delete_expense'])) {
      $stmt = $db->prepare("DELETE FROM expenses WHERE id = :id");
      $stmt->execute([':id' => $_POST['id']]);
    } elseif (isset($_POST['add_income'])) {
      $stmt = $db->prepare("INSERT INTO income (source, amount, date) VALUES (:source, :amount, :date)");
      $stmt->execute([
        ':source' => $_POST['source'],
        ':amount' => $_POST['amount'],
        ':date' => $_POST['date']
      ]);
    } elseif (isset($_POST['add_expense'])) {
      $stmt = $db->prepare("INSERT INTO expenses (category, description, amount, date) VALUES (:category, :description, :amount, :date)");
      $stmt->execute([
        ':category' => $_POST['category'],
        ':description' => $_POST['description'],
        ':amount' => $_POST['amount'],
        ':date' => $_POST['date']
      ]);
    }
  }

  // Calculate totals
  $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
  $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

  $income_result = $db->query("SELECT SUM(amount) FROM income WHERE date BETWEEN '$start_date' AND '$end_date'")->fetchColumn();
  $expense_result = $db->query("SELECT SUM(amount) FROM expenses WHERE date BETWEEN '$start_date' AND '$end_date'")->fetchColumn();
  
  $income_total = ($income_result !== false) ? $income_result : 0;
  $expense_total = ($expense_result !== false) ? $expense_result : 0;
  $balance = $income_total - $expense_total;

} catch (PDOException $ex) {
  echo $ex->getMessage();
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Expense Tracker</title>
  <style>
    body { 
      font-family: Arial; 
      max-width: 800px; 
      margin: 0 auto; 
      padding: 10px; 
    }
    .form-group { 
      margin: 10px 0; 
    }
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 8px;
      margin-top: 4px;
      box-sizing: border-box;
    }
    .summary { 
      background: #f0f0f0; 
      padding: 15px; 
      margin: 20px 0;
      border-radius: 8px;
    }
    .positive { color: green; }
    .negative { color: red; }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 20px;
    }
    th, td { 
      padding: 8px; 
      border: 1px solid #ddd; 
    }
    .forms-container {
   
      gap: 20px;
      flex-wrap: wrap;
    }
    .form-section {
      flex: 1;
      min-width: 300px;
    }
    button {
      padding: 8px 16px;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover {
      background: #45a049;
    }
    @media (max-width: 600px) {
      body {
        padding: 8px;
      }
      table {
        display: block;
        overflow-x: auto;
      }
      .form-section {
        min-width: 100%;
      }
      th, td {
        white-space: nowrap;
      }
      .forms-container {
     flex-direction: column;
    }
    }
  </style>
</head>
<body>
  <h1>Expense Tracker</h1>

  <div class="summary">
    <h2>Current Status (<?= $start_date ?> to <?= $end_date ?>)</h2>
    <p>Total Income: $<?= number_format($income_total, 2) ?></p>
    <p>Total Expenses: $<?= number_format($expense_total, 2) ?></p>
    <p>Balance: <span class="<?= $balance >= 0 ? 'positive' : 'negative' ?>">
      $<?= number_format($balance, 2) ?>
    </span></p>
  </div>

  <form method="GET">
    <div class="form-group">
      <label>Date Range:</label>
      <input type="date" name="start_date" value="<?= $start_date ?>">
      <input type="date" name="end_date" value="<?= $end_date ?>">
      <button type="submit">Filter</button>
    </div>
  </form>

  <div class="forms-container">
    <div class="form-section">
      <h3>Add Income</h3>
      <form method="POST">
        <div class="form-group">
          <label>Source:</label>
          <select name="source" required>
            <option value="Paycheck">Paycheck</option>
            <option value="Roommate Rent">Roommate Rent</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount:</label>
          <input type="number" step="0.01" name="amount" required>
        </div>
        <div class="form-group">
          <label>Date:</label>
          <input type="date" name="date" required>
        </div>
        <button type="submit" name="add_income">Add Income</button>
      </form>
    </div>

    <div class="form-section">
      <h3>Add Expense</h3>
      <form method="POST">
        <div class="form-group">
          <label>Category:</label>
          <select name="category" required>
            <option value="Monthly Bills">Monthly Bills</option>
            <option value="Daily Spending">Daily Spending</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description:</label>
          <input type="text" name="description" required>
        </div>
        <div class="form-group">
          <label>Amount:</label>
          <input type="number" step="0.01" name="amount" required>
        </div>
        <div class="form-group">
          <label>Date:</label>
          <input type="date" name="date" required>
        </div>
        <button type="submit" name="add_expense">Add Expense</button>
      </form>
    </div>
  </div>

  <h3>Recent Transactions</h3>
  <table>
    <tr>
      <th>Date</th>
      <th>Type</th>
      <th>Category/Source</th>
      <th>Description</th>
      <th>Amount</th>
      <th>Action</th>
    </tr>
    <?php
    $transactions = $db->query("
      SELECT id, date, 'Income' as type, source as category, source as description, amount 
      FROM income 
      WHERE date BETWEEN '$start_date' AND '$end_date'
      UNION ALL
      SELECT id, date, 'Expense' as type, category, description, -amount 
      FROM expenses 
      WHERE date BETWEEN '$start_date' AND '$end_date'
      ORDER BY date DESC
    ");
    
    foreach ($transactions as $trans) {
      echo "<tr>";
      echo "<td>" . $trans['date'] . "</td>";
      echo "<td>" . $trans['type'] . "</td>";
      echo "<td>" . $trans['category'] . "</td>";
      echo "<td>" . $trans['description'] . "</td>";
      echo "<td class='" . ($trans['amount'] >= 0 ? 'positive' : 'negative') . "'>" . 
           number_format(abs($trans['amount']), 2) . "</td>";
      echo "<td>
        <form method='POST' style='display:inline;'>
          <input type='hidden' name='id' value='" . $trans['id'] . "'>
          <button type='submit' name='" . ($trans['amount'] >= 0 ? 'delete_income' : 'delete_expense') . "' 
            onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</button>
        </form>
      </td>";
      echo "</tr>";
    }
    ?>
  </table>
</body>
</html>
