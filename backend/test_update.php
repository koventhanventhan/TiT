<?php
use App\Models\ZoomSchedule;
$s = ZoomSchedule::find(309);
$s->update(['reminded_at' => now()]);
echo "Reminded At: " . $s->reminded_at . "\n";
