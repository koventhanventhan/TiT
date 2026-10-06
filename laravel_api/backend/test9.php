<?php
function parseSubjects($subjects) {
    if (is_array($subjects)) {
        return $subjects;
    }
    
    if (is_string($subjects)) {
        $decoded = json_decode($subjects, true);
        if (is_array($decoded)) {
            return $decoded;
        } elseif (is_string($decoded)) {
            $decoded2 = json_decode($decoded, true);
            if (is_array($decoded2)) {
                return $decoded2;
            } else {
                return array_map('trim', explode(',', $decoded));
            }
        } else {
            return array_map('trim', explode(',', $subjects));
        }
    }
    return [];
}

var_dump(parseSubjects('["ICT","arts","drama"]'));
var_dump(parseSubjects('"[\"ICT\",\"arts\",\"drama\"]"'));
var_dump(parseSubjects('Tamil, English'));
var_dump(parseSubjects('"Tamil, English"'));
var_dump(parseSubjects(['ICT', 'arts', 'drama']));
