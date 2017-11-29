<?php
$doc = new DOMDocument();
$doc->load('SkierLogs.xml');
$exe = new DOMXPath($doc);


$pdo = new PDO('mysql:host=localhost;dbname=DBOblig5;charset=utf8mb4', 'root', '');

parseClubs($exe);
echo "20%\n";
parseSeason($exe);
echo "40%\n";
parseSkiers($exe);
echo "60%\n";
parseLogs($exe);
echo "80%\n";
parseTotalDistance($exe);
echo "100%\nDONE!\n\n";

function getNode($item, $node){
  return $item->getElementsByTagName($node)[0]->nodeValue;
}

function parseClubs($exe) {
  global $pdo;
  $data = $exe->query("//SkierLogs/Clubs/Club");

  foreach ($data as $item) {

    $id = $item->getAttribute('id');
    $name = getNode($item, 'Name');
    $city = getNode($item, 'City');
    $county = getNode($item, 'County');
    //echo $id . " - " . $name . " - " . $city . " - " . $county . "\n"; //DEBUG
    $prepared = $pdo->prepare('INSERT INTO Clubs (clubId, name, city, county) VALUES (:clubId, :name, :city, :county)');
    $prepared->execute([
      ':clubId' => $id,
      ':name' => $name,
      ':city' => $city,
      ':county' => $county
    ]);
  }
}


function parseSeason($exe) {
  global $pdo;
  $data = $exe->query("//SkierLogs/Season");

  foreach ($data as $item) {
    $fallYear = $item->getAttribute('fallYear');
    //echo $fallYear . "\n"; // DEBUG

    $prepared = $pdo->prepare('INSERT INTO Season (fallYear) VALUES (:fallYear)');
    $prepared->execute([
      ':fallYear' => $fallYear
    ]);
  }
}

function parseSkiers($exe) {
  global $pdo;
  $data = $exe->query("//SkierLogs/Skiers/Skier");

  foreach ($data as $item) {
    $userName = $item->getAttribute('userName');
    $firstName = getNode($item, 'FirstName');
    $lastName = getNode($item, 'LastName');
    $yearOfBirth = getNode($item, 'YearOfBirth');
    //echo $userName . " - " . $firstName . " - " . $lastName . " - " . $yearOfBirth . " - " . "\n"; //DEBUG

    $prepared = $pdo->prepare('INSERT INTO Skiers (userName, firstName, lastName, yearOfBirth) VALUES (:userName, :firstName, :lastName, :yearOfBirth)');
    $prepared->execute([
      ':userName' => $userName,
      ':firstName' => $firstName,
      ':lastName' => $lastName,
      ':yearOfBirth' => $yearOfBirth
    ]);
  }
}

function parseLogs($exe) {
  global $pdo;
  $data = $exe->query("//Season/Skiers");
  foreach ($data as $item) {
    $seasonPath = $exe->query("../@fallYear", $item);
    $fallYear = $seasonPath[0]->value;

    $clubId = "";
    if ($item->hasAttribute('clubId')) {
      $clubId = $item->getAttribute('clubId');
    }

    $skiers = $exe->query("Skier", $item);

    foreach ($skiers as $skier) {
      $userName = $skier->getAttribute('userName');
      $logs = $exe->query('Log/Entry', $skier);

      foreach ($logs as $log) {
          $date = getNode($log, 'Date');
          $area = getNode($log, 'Area');
          $distance = getNode($log, 'Distance');
          $prepared = $pdo->prepare('INSERT INTO Logs (season, clubId, userName, date, area, distance) VALUES (:season, :clubId, :userName, :date, :area, :distance)');
          $prepared->execute([
            ':season' => $fallYear,
            ':clubId' => $clubId,
            ':userName' => $userName,
            ':date' => $date,
            ':area' => $area,
            ':distance' => $distance
          ]);
      }
    }
  }
}

function parseTotalDistance($exe) {
  global $pdo;
  $data = $exe->query("//Season/Skiers/Skier");

  foreach ($data as $item) {
    $userName = $item->getAttribute('userName');
    $seasonPath = $exe->query("../../@fallYear", $item);
    $fallYear = $seasonPath[0]->value;
    $totalDistances = $exe->query('//Season[@fallYear="'.$fallYear.'"]/Skiers/Skier[@userName="'.$userName.'"]/Log/Entry');
    $distance = 0;

    foreach($totalDistances as $totalDistance) {
      $distance += getNode($totalDistance, 'Distance');
    }

    $prepared = $pdo->prepare('INSERT INTO TotalDistance (userName, fallYear, totalDistance) VALUES (:userName, :fallYear, :totalDistance)');
    $prepared->execute([
      ':fallYear' => $fallYear,
      ':userName' => $userName,
      ':totalDistance' => $distance
    ]);
  }
}
