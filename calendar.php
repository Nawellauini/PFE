<?php
// Inclure le fichier de configuration pour la connexion à la base de données
require_once 'db_config.php';


// Démarrer la session
session_start();

// Vérifier si le professeur est connecté
if (!isset($_SESSION['id_professeur'])) {
    header("Location: login.php");
    exit();
}

$id_professeur = $_SESSION['id_professeur'];

// Récupérer les informations du professeur
$queryProf = $conn->prepare("SELECT * FROM professeurs WHERE id_professeur = ?");
$queryProf->bind_param("i", $id_professeur);
$queryProf->execute();
$result = $queryProf->get_result();

// Vérifier si le professeur existe
if ($result->num_rows === 0) {
    // Rediriger vers la page de connexion si le professeur n'existe pas
    session_destroy();
    header("Location: login.php");
    exit();
}

$prof = $result->fetch_assoc();
$nomProf = $prof['prenom'] . ' ' . $prof['nom'];

// Récupérer le mois et l'année actuels ou ceux spécifiés dans l'URL
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Vérifier que le mois est valide (entre 1 et 12)
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Récupérer les événements du mois pour ce professeur
$firstDayOfMonth = "$year-$month-01";
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

// Vérifier si la table calendar_events existe
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'calendar_events'");
if ($checkTable->num_rows > 0) {
    $tableExists = true;
}

$events = [];
$eventsByDate = [];

if ($tableExists) {
    $queryEvents = $conn->prepare("SELECT ce.*, c.nom_classe 
                                  FROM calendar_events ce
                                  JOIN classes c ON ce.class = c.id_classe
                                  WHERE ce.professor_id = ? 
                                  AND ce.event_date BETWEEN ? AND ?
                                  ORDER BY ce.event_date ASC");
    
    if ($queryEvents) {
        $queryEvents->bind_param("iss", $id_professeur, $firstDayOfMonth, $lastDayOfMonth);
        if ($queryEvents->execute()) {
            $events = $queryEvents->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Organiser les événements par date
            foreach ($events as $event) {
                $date = $event['event_date'];
                if (!isset($eventsByDate[$date])) {
                    $eventsByDate[$date] = [];
                }
                $eventsByDate[$date][] = $event;
            }
        } else {
            // Gérer l'erreur d'exécution
            $error = $conn->error;
        }
    } else {
        // Gérer l'erreur de préparation
        $error = $conn->error;
    }
}

// Récupérer les classes du professeur pour le filtre
$classes = [];
$queryClasses = $conn->prepare("SELECT c.id_classe, c.nom_classe 
                               FROM professeurs_classes pc
                               JOIN classes c ON pc.id_classe = c.id_classe
                               WHERE pc.id_professeur = ?");
if ($queryClasses) {
    $queryClasses->bind_param("i", $id_professeur);
    if ($queryClasses->execute()) {
        $classes = $queryClasses->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Filtrer par classe si spécifié
$filteredClassId = isset($_GET['class']) ? intval($_GET['class']) : 0;
if ($filteredClassId > 0 && $tableExists) {
    $queryFilteredEvents = $conn->prepare("SELECT ce.*, c.nom_classe 
                                          FROM calendar_events ce
                                          JOIN classes c ON ce.class = c.id_classe
                                          WHERE ce.professor_id = ? 
                                          AND ce.class = ?
                                          AND ce.event_date BETWEEN ? AND ?
                                          ORDER BY ce.event_date ASC");
    if ($queryFilteredEvents) {
        $queryFilteredEvents->bind_param("iiss", $id_professeur, $filteredClassId, $firstDayOfMonth, $lastDayOfMonth);
        if ($queryFilteredEvents->execute()) {
            $events = $queryFilteredEvents->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Réorganiser les événements filtrés par date
            $eventsByDate = [];
            foreach ($events as $event) {
                $date = $event['event_date'];
                if (!isset($eventsByDate[$date])) {
                    $eventsByDate[$date] = [];
                }
                $eventsByDate[$date][] = $event;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرزنامة | منصة المدرس</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e6091;
            --primary-dark: #0d4a77;
            --secondary-color: #2a9d8f;
            --secondary-dark: #1f756a;
            --accent-color: #e9c46a;
            --text-color: #264653;
            --text-light: #546a7b;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --error-color: #e76f51;
            --success-color: #2a9d8f;
            --warning-color: #f4a261;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Amiri', serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
        }

        .calendar-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 2rem;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .calendar-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .calendar-title i {
            color: var(--accent-color);
        }

        .calendar-nav {
            display: flex;
            gap: 1rem;
        }

        .calendar-nav button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calendar-nav button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
        }

        .calendar-day-header {
            text-align: center;
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            font-weight: bold;
        }

        .calendar-day {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            min-height: 120px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid var(--border-color);
        }

        .calendar-day:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .calendar-day.today {
            background-color: var(--accent-color);
            color: white;
            border: 2px solid var(--primary-color);
        }

        .calendar-day.other-month {
            opacity: 0.5;
            background-color: var(--bg-color);
        }

        .day-number {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .calendar-day.today .day-number {
            color: white;
        }

        .event-list {
            list-style: none;
            margin-top: 0.5rem;
        }

        .event-item {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .event-item:hover {
            background-color: var(--primary-dark);
        }

        .event-item.past {
            opacity: 0.7;
            background-color: var(--text-light);
        }

        .add-event-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: var(--success-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .add-event-btn:hover {
            transform: scale(1.1);
            background-color: var(--secondary-dark);
        }

        .event-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .event-modal-content {
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
        }

        .event-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .event-modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .calendar-grid {
                grid-template-columns: repeat(1, 1fr);
            }
            
            .calendar-day {
                min-height: auto;
            }
            
            .event-list {
                display: none;
            }
            
            .calendar-day:hover .event-list {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="calendar-container">
            <div class="calendar-header">
                <h1 class="calendar-title">
                    <i class="fas fa-calendar-alt"></i>
                    الرزنامة
                </h1>
                <div class="calendar-nav">
                    <button id="prevMonth">
                        <i class="fas fa-chevron-right"></i>
                        الشهر السابق
                    </button>
                    <button id="nextMonth">
                        الشهر القادم
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </div>
            </div>

            <div class="calendar-grid" id="calendarGrid">
                <!-- Les jours de la semaine -->
                <div class="calendar-day-header">الأحد</div>
                <div class="calendar-day-header">الاثنين</div>
                <div class="calendar-day-header">الثلاثاء</div>
                <div class="calendar-day-header">الأربعاء</div>
                <div class="calendar-day-header">الخميس</div>
                <div class="calendar-day-header">الجمعة</div>
                <div class="calendar-day-header">السبت</div>

                <!-- Les jours du mois seront ajoutés dynamiquement ici -->
            </div>
        </div>

        <button class="add-event-btn" id="addEventBtn">
            <i class="fas fa-plus"></i>
        </button>

        <!-- Modal pour ajouter un événement -->
        <div class="event-modal" id="eventModal">
            <div class="event-modal-content">
                <div class="event-modal-header">
                    <h2 class="event-modal-title">إضافة موعد جديد</h2>
                    <button class="close-modal" id="closeModal">&times;</button>
                </div>
                <form id="eventForm">
                    <div class="form-group">
                        <label for="eventTitle">عنوان الموعد</label>
                        <input type="text" id="eventTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="eventDate">التاريخ</label>
                        <input type="date" id="eventDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="eventDescription">الوصف</label>
                        <textarea id="eventDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">حفظ</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarGrid = document.getElementById('calendarGrid');
            const addEventBtn = document.getElementById('addEventBtn');
            const eventModal = document.getElementById('eventModal');
            const closeModal = document.getElementById('closeModal');
            const eventForm = document.getElementById('eventForm');
            const prevMonthBtn = document.getElementById('prevMonth');
            const nextMonthBtn = document.getElementById('nextMonth');

            let currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();

            function generateCalendar() {
                // Vider le calendrier
                while (calendarGrid.children.length > 7) {
                    calendarGrid.removeChild(calendarGrid.lastChild);
                }

                // Obtenir le premier jour du mois
                const firstDay = new Date(currentYear, currentMonth, 1);
                const startingDay = firstDay.getDay();

                // Obtenir le nombre de jours dans le mois
                const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

                // Ajouter les jours vides au début
                for (let i = 0; i < startingDay; i++) {
                    const emptyDay = document.createElement('div');
                    emptyDay.className = 'calendar-day other-month';
                    calendarGrid.appendChild(emptyDay);
                }

                // Ajouter les jours du mois
                for (let day = 1; day <= daysInMonth; day++) {
                    const dayElement = document.createElement('div');
                    dayElement.className = 'calendar-day';
                    
                    // Vérifier si c'est aujourd'hui
                    if (day === currentDate.getDate() && 
                        currentMonth === currentDate.getMonth() && 
                        currentYear === currentDate.getFullYear()) {
                        dayElement.classList.add('today');
                    }

                    const dayNumber = document.createElement('div');
                    dayNumber.className = 'day-number';
                    dayNumber.textContent = day;
                    dayElement.appendChild(dayNumber);

                    // Ajouter la liste des événements
                    const eventList = document.createElement('ul');
                    eventList.className = 'event-list';
                    dayElement.appendChild(eventList);

                    calendarGrid.appendChild(dayElement);
                }
            }

            // Gérer les boutons de navigation
            prevMonthBtn.addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                generateCalendar();
            });

            nextMonthBtn.addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                generateCalendar();
            });

            // Gérer le modal
            addEventBtn.addEventListener('click', function() {
                eventModal.style.display = 'flex';
            });

            closeModal.addEventListener('click', function() {
                eventModal.style.display = 'none';
            });

            eventForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Ici, vous pouvez ajouter la logique pour sauvegarder l'événement
                eventModal.style.display = 'none';
            });

            // Générer le calendrier initial
            generateCalendar();
        });
    </script>
</body>
</html>

