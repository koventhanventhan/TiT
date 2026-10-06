<?php
$subjects = '"[\"ICT\",\"arts\",\"drama\"]"';
$trimmedSubjects = trim($subjects);
$selectedSubjects = [];
if (str_starts_with($trimmedSubjects, '[')) {
    $selectedSubjects = json_decode($trimmedSubjects, true) ?? [];
} else {
    $selectedSubjects = array_map('trim', explode(',', $trimmedSubjects));
}
var_dump($selectedSubjects);
