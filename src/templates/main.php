<!-- https://github.com/DenverCoder1/readme-typing-svg/ -->
<?php
$hayFondo = $background !== "#00000000";
$hayBorde = $borderColor !== "#00000000";
// texto plano de cada linea, para medir el largo real
$planos = array_map(function ($l) {
    return html_entity_decode(strip_tags($l), ENT_QUOTES, "UTF-8");
}, $lines);
$largos = array_map("mb_strlen", $planos);
$maxLargo = max($largos) ?: 1;
?>
<svg xmlns='http://www.w3.org/2000/svg'
    xmlns:xlink='http://www.w3.org/1999/xlink'
    viewBox='0 0 <?= "$width $height" ?>'
    width='<?= $width ?>px' height='<?= $height ?>px'>

    <?= $fontCSS ?>

    <style>
        /* cursor de bloque: parpadea mientras la linea esta escrita */
        .cursor { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
    </style>

<?php if ($hayFondo || $hayBorde): ?>
    <rect x='0.5' y='0.5' width='<?= $width - 1 ?>' height='<?= $height - 1 ?>'
        rx='<?= $borderRadius ?>'
        fill='<?= $hayFondo ? $background : "none" ?>'<?php if ($hayBorde): ?>
        stroke='<?= $borderColor ?>' stroke-width='1'<?php endif; ?> />
<?php endif; ?>

    <?php $lastLineIndex = count($lines) - 1; ?>
    <?php for ($i = 0; $i <= $lastLineIndex; ++$i): ?>
        <?php
        $x0 = $padding;
        $anchoUtil = max(1, $width - 2 * $padding);
        ?>
        <path id='path<?= $i ?>'>
            <?php if (!$multiline): ?>
                <!-- Single line -->
                <?php
                // arranca cuando termina la anterior
                $begin = "d" . ($i - 1) . ".end";
                if ($i == 0) {
                    $begin = $repeat ? "0s;d$lastLineIndex.end" : "0s";
                }
                // si no se repite, la ultima linea queda escrita
                $freeze = !$repeat && $i == $lastLineIndex;

                // Los tiempos son PROPORCIONALES al largo de cada linea: asi todas
                // se escriben y se borran a la misma velocidad por caracter, en vez
                // de que las largas parezcan apuradas.
                $prop = max(0.3, $largos[$i] / $maxLargo);
                $tEscribir = $duration * $prop;
                $tSostener = $pause;
                $tBorrar = $duration * $prop * 0.35;
                $tReposo = $freeze ? 0 : $restPause;
                $total = max(1, $tEscribir + $tSostener + $tBorrar + $tReposo);

                $k1 = $tEscribir / $total;
                $k2 = ($tEscribir + $tSostener) / $total;
                $k3 = ($tEscribir + $tSostener + $tBorrar) / $total;

                $yOffset = $height / 2;
                $vacia = "m$x0,$yOffset h0";
                $llena = "m$x0,$yOffset h$anchoUtil";
                $values = $freeze
                    ? [$vacia, $llena, $llena, $llena, $llena]
                    : [$vacia, $llena, $llena, $vacia, $vacia];
                $keyTimes = ["0", $k1, $k2, $k3, "1"];
                ?>
                <animate id='d<?= $i ?>' attributeName='d' begin='<?= $begin ?>'
                    dur='<?= round($total) ?>ms' fill='<?= $freeze ? "freeze" : "remove" ?>'
                    values='<?= implode(" ; ", $values) ?>' keyTimes='<?= implode(";", $keyTimes) ?>' />
            <?php else: ?>
                <!-- Multiline -->
                <?php
                $nextIndex = $i + 1;
                $lineHeight = $size + 5;
                $lineDuration = ($duration + $pause) * $nextIndex;
                $yOffset = $nextIndex * $lineHeight;
                $vacia = "m$x0,$yOffset h0";
                $llena = "m$x0,$yOffset h$anchoUtil";
                $values = [$vacia, $vacia, $llena, $llena];
                $keyTimes = ["0", $i / $nextIndex, $i / $nextIndex + $duration / $lineDuration, "1"];
                ?>
                <animate id='d<?= $i ?>' attributeName='d' begin='0s<?= $repeat ? ";d$lastLineIndex.end" : "" ?>'
                    dur='<?= $lineDuration ?>ms' fill="freeze"
                    values='<?= implode(" ; ", $values) ?>' keyTimes='<?= implode(";", $keyTimes) ?>' />
            <?php endif; ?>
        </path>
    <text font-family='"<?= $font ?>", monospace' fill='<?= $color ?>' font-size='<?= $size ?>'
        dominant-baseline='<?= $vCenter ? "middle" : "auto" ?>'
        x='<?= $center ? "50%" : $padding ?>' text-anchor='<?= $center ? "middle" : "start" ?>'
        letter-spacing='<?= $letterSpacing ?>'>
        <textPath xlink:href='#path<?= $i ?>'>
            <?= $lines[$i] ?><tspan class='cursor'>&#9608;</tspan>
        </textPath>
    </text>
<?php if (!$multiline && !$freeze && $tReposo > 0): ?>
        <?php
        // Durante la pausa en vacio la linea no muestra nada, asi que el cursor
        // se dibuja aparte y parpadea un par de veces antes de la siguiente linea.
        $resto = max(0.001, 1 - $k3);
        $m1 = $k3 + $resto * 0.25;
        $m2 = $k3 + $resto * 0.50;
        $m3 = $k3 + $resto * 0.75;
        ?>
    <text font-family='"<?= $font ?>", monospace' fill='<?= $color ?>' font-size='<?= $size ?>'
        dominant-baseline='<?= $vCenter ? "middle" : "auto" ?>'
        x='<?= $center ? "50%" : $padding ?>' text-anchor='<?= $center ? "middle" : "start" ?>'
        opacity='0'>&#9608;<animate attributeName='opacity' begin='d<?= $i ?>.begin'
            dur='<?= round($total) ?>ms' calcMode='discrete'
            values='0;1;0;1;0;0' keyTimes='0;<?= $k3 ?>;<?= $m1 ?>;<?= $m2 ?>;<?= $m3 ?>;1' /></text>
<?php endif; ?>
<?php endfor; ?>
</svg>
