<?php
$exe = simplexml_load_file("SkierLogs.xml");

$pdo = new PDO('mysql:host=localhost;dbname=DBOblig5;charset=utf8mb4', 'root', '');

parseClubs($exe);
parseSeason($exe);
parseSkiers($exe);
parseLogs($exe);
parseTotalDistance($exe);

function parseClubs($exe) {
  global $pdo;
  $data = $exe->xpath("//SkierLogs/Clubs/Club");
  echo("<br />");
  echo("<br />");
  echo("CLUBS: ");
  echo("<br />");
  echo("<br />");
  echo("<hr />");

  foreach ($data as $item) {
    $row = simplexml_load_string($item->asXML());
    $attributes = $row->attributes();
    $id = $attributes->id;
    $name = $row->Name;
    $city = $row->City;
    $county = $row->County;

    echo("id: ");
    echo($id);
    echo("<br />");

    echo("Name: ");
    echo($name);
    echo("<br />");

    echo("City: ");
    echo($city);
    echo("<br />");

    echo("County: ");
    echo($county);
    echo("<hr />");


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
  $data = $exe->xpath("//SkierLogs/Season");
  echo("<br />");
  echo("<br />");
  echo("SEASONS: ");
  echo("<br />");
  echo("<br />");
  echo("<hr />");

  foreach ($data as $item) {
    $row = simplexml_load_string($item->asXML());
    $attributes = $row->attributes();
    $fallYear = $attributes->fallYear;

    echo("fallYear: ");
    echo($fallYear);
    echo("<hr />");

    $prepared = $pdo->prepare('INSERT INTO Season (fallYear) VALUES (:fallYear)');
    $prepared->execute([
      ':fallYear' => $fallYear
    ]);
  }
}

function parseSkiers($exe) {
  global $pdo;
  $data = $exe->xpath("//SkierLogs/Skiers/Skier");
  echo("<br />");
  echo("<br />");
  echo("SKIERS: ");
  echo("<br />");
  echo("<br />");
  echo("<hr />");

  foreach ($data as $item) {
    $row = simplexml_load_string($item->asXML());
    $attributes = $row->attributes();
    $userName = $attributes->userName;
    $firstName = $row->FirstName;
    $lastName = $row->LastName;
    $yearOfBirth = $row->YearOfBirth;

    echo("USERNAME: ");
    echo($userName);
    echo("<br />");

    echo("FirstName: ");
    echo($firstName);
    echo("<br />");

    echo("LastName: ");
    echo($lastName);
    echo("<br />");

    echo("YearOfBirth: ");
    echo($yearOfBirth);
    echo("<hr />");


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
  $data = $exe->xpath("//Season/Skiers");
  echo("<br />");
  echo("<br />");
  echo("LOGS: ");
  echo("<br />");
  echo("<br />");
  echo("<hr />");

  foreach ($data as $item) {
    $row = simplexml_load_string($item->asXML());
    $seasonPath = $item->xpath("../@fallYear");
    $fallYear = $seasonPath[0];
    echo("<hr />");
    echo("fallYear: ");
    echo($fallYear);
    echo("<br />");


    $clubAttributes = $row->attributes();
    $clubId = "";
    if(isset ($clubAttributes->clubId)) {
      $clubId = $clubAttributes->clubId;
    }

    echo("clubId: ");
    echo($clubId);
    echo("<br />");
    echo("<br />");

    $skiers = $item->xpath("Skier");
    foreach ($skiers as $skier) {
      $skierRow = simplexml_load_string($skier->asXML());
      $skierAttributes = $skierRow->attributes();
      $userName = $skierAttributes->userName;
      echo("userName: ");
      echo($userName);
      echo("<br />");

      $logs = $skier->xpath("Log/Entry");
      foreach ($logs as $log) {
          $logRow = simplexml_load_string($log->asXML());
          $date = $logRow->Date;
          $area = $logRow->Area;
          $distance = $logRow->Distance;

            echo("Date: ");
            echo($date);
            echo("<br />");

            echo("Area: ");
            echo($area);
            echo("<br />");

            echo("Distance: ");
            echo($distance);
            echo("<br />");
            echo("<br />");


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
  $data = $exe->xpath("//Season/Skiers/Skier");

  foreach ($data as $item) {
    $row = simplexml_load_string($item->asXML());
    $attributes = $row->attributes();
    $userName = $attributes->userName;
    $seasonPath = $item->xpath("../../@fallYear");
    $fallYear = $seasonPath[0];
    $totalDistances = $exe->xpath('//Season[@fallYear="'.$fallYear.'"]/Skiers/Skier[@userName="'.$userName.'"]/Log/Entry');
    $distance = 0;
    foreach($totalDistances as $totalDistance) {
      $distanceRow = simplexml_load_string($totalDistance->asXML());
      $distance += $distanceRow->Distance;

    }
    $prepared = $pdo->prepare('INSERT INTO TotalDistance (userName, fallYear, totalDistance) VALUES (:userName, :fallYear, :totalDistance)');
    $prepared->execute([
      ':fallYear' => $fallYear,
      ':userName' => $userName,
      ':totalDistance' => $distance
    ]);
  }
}
