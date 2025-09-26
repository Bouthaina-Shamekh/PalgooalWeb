<?php
use App\Models\Page;
foreach (Page::with('sections')->get() as ) {
    echo ->id.' '.count(->sections).' '.implode(',', ->sections->pluck('key')->all())."\n";
}
