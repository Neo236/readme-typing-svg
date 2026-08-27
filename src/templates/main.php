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
// Fira Code es monoespaciada y TODOS sus glifos miden 0.6 em, el cursor de
// bloque U+2588 incluido (verificado sobre el hmtx de la fuente).
if (!defined("AVANCE_EM")) {
    define("AVANCE_EM", 0.6);
}
?>
<svg xmlns='http://www.w3.org/2000/svg'
    xmlns:xlink='http://www.w3.org/1999/xlink'
    viewBox='0 0 <?= "$width $height" ?>'
    width='<?= $width ?>px' height='<?= $height ?>px'>

    <?= $fontCSS ?>

    <style>
        /* Solo para el cursor pegado al texto (center=true o multilinea). El
           cursor de terminal no usa esta clase: su parpadeo va por SMIL para
           poder quedarse solido mientras se escribe y mientras se borra. */
        .cursor { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

        /* Esto se anima solo y no para nunca, que es justo lo que WCAG 2.2.2
           pide poder frenar. CSS no puede detener SMIL, pero si puede esconder
           lo animado y mostrar en su lugar una linea quieta. */
        .fijo { display: none; }
        @media (prefers-reduced-motion: reduce) {
            .anim { display: none; }
            .fijo { display: block; }
        }
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

        // El texto se revela recortandolo con el largo del path. Si el path
        // crece hasta el ancho util completo, una linea corta queda entera en
        // el primer tramo de la animacion y despues no pasa nada: parecia
        // aparecer de golpe en vez de escribirse. Creciendo solo hasta donde
        // llega el texto, el revelado ocupa todo el tiempo de escritura y
        // todas las lineas van a la misma velocidad por caracter.
        //
        // En modo terminal el cursor se dibuja aparte (ver mas abajo), asi que
        // no ocupa lugar en el path. Con center=true o multilinea sigue yendo
        // pegado al texto y hay que contarlo.
        $terminal = !$center && !$multiline;
        $anchoTexto = ($largos[$i] + ($terminal ? 0 : 1)) * $size * AVANCE_EM;
        $recorrido = $terminal
            ? max(1, min($anchoUtil, (int) ceil($anchoTexto)))
            : $anchoUtil;
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
                $prop = $largos[$i] / $maxLargo;
                $tEscribir = $duration * $prop;
                $tSostener = $pause;
                $tBorrar = $duration * $prop * 0.35;
                $tReposo = $freeze ? 0 : $restPause;
                $total = max(1, $tEscribir + $tSostener + $tBorrar + $tReposo);

                $k1 = $tEscribir / $total;
                $k2 = ($tEscribir + $tSostener) / $total;
                $k3 = ($tEscribir + $tSostener + $tBorrar) / $total;

                $yOffset = $height / 2;

                // UN CARACTER POR PASO. Una consola no revela el texto
                // deslizandolo: aparece una celda entera por vez, y el cursor
                // salta a la celda siguiente. Con calcMode='discrete' el path
                // toma exactamente m anchos de caracter, asi que se dibujan m
                // glifos justos y el cursor cae pegado al ultimo, sin el medio
                // caracter de solape que dejaba la interpolacion continua.
                $ancho = $size * AVANCE_EM;      // una celda
                $n = max(1, $largos[$i]);
                $values = [];
                $cursorX = [];
                $keyTimes = [];
                for ($m = 0; $m <= $n; $m++) {          // escritura
                    $keyTimes[] = round($k1 * $m / $n, 5);
                    $values[] = "m$x0,$yOffset h" . round($m * $ancho, 1);
                    $cursorX[] = round($x0 + $m * $ancho, 1);
                }
                if (!$freeze) {                          // borrado
                    for ($j = 1; $j <= $n; $j++) {
                        $keyTimes[] = round($k2 + ($k3 - $k2) * $j / $n, 5);
                        $values[] = "m$x0,$yOffset h" . round(($n - $j) * $ancho, 1);
                        $cursorX[] = round($x0 + ($n - $j) * $ancho, 1);
                    }
                }
                if ((float) $keyTimes[count($keyTimes) - 1] < 1) {
                    $keyTimes[] = "1";                   // SMIL exige cerrar en 1
                    $values[] = $values[count($values) - 1];
                    $cursorX[] = $cursorX[count($cursorX) - 1];
                }

                // El cursor esta SOLIDO mientras se escribe y mientras se
                // borra, y parpadea solo cuando la terminal quedo quieta.
                $medioParpadeo = 500;   // ms
                $opTiempos = ["0"];
                $opValores = ["1"];
                $parpadear = function ($desde, $hasta) use (&$opTiempos, &$opValores, $total, $medioParpadeo) {
                    for ($j = 1; ; $j++) {
                        $t = $desde + ($medioParpadeo * $j) / $total;
                        if ($t >= $hasta) {
                            return;
                        }
                        $opTiempos[] = round($t, 5);
                        $opValores[] = $j % 2 === 1 ? "0" : "1";
                    }
                };
                $parpadear($k1, $freeze ? 1 : $k2);
                if (!$freeze) {
                    if ($k2 > (float) $opTiempos[count($opTiempos) - 1]) {
                        $opTiempos[] = round($k2, 5);
                        $opValores[] = "1";
                    }
                    $parpadear($k3, 1);
                }
                if ((float) $opTiempos[count($opTiempos) - 1] < 1) {
                    $opTiempos[] = "1";
                    $opValores[] = $opValores[count($opValores) - 1];
                }
                ?>
                <animate id='d<?= $i ?>' attributeName='d' begin='<?= $begin ?>'
                    dur='<?= round($total) ?>ms' fill='<?= $freeze ? "freeze" : "remove" ?>'
                    calcMode='discrete'
                    values='<?= implode(";", $values) ?>' keyTimes='<?= implode(";", $keyTimes) ?>' />
            <?php else: ?>
                <!-- Multiline -->
                <?php
                $nextIndex = $i + 1;
                $lineHeight = $size + 5;
                $lineDuration = ($duration + $pause) * $nextIndex;
                $yOffset = $nextIndex * $lineHeight;
                $vacia = "m$x0,$yOffset h0";
                $llena = "m$x0,$yOffset h$recorrido";
                $values = [$vacia, $vacia, $llena, $llena];
                $keyTimes = ["0", $i / $nextIndex, $i / $nextIndex + $duration / $lineDuration, "1"];
                ?>
                <animate id='d<?= $i ?>' attributeName='d' begin='0s<?= $repeat ? ";d$lastLineIndex.end" : "" ?>'
                    dur='<?= $lineDuration ?>ms' fill="freeze"
                    values='<?= implode(" ; ", $values) ?>' keyTimes='<?= implode(";", $keyTimes) ?>' />
            <?php endif; ?>
        </path>
    <?php
    // OJO: cuando el texto va sobre un textPath, el atributo x del <text> NO
    // lo posiciona, se SUMA como desplazamiento sobre el path. Como el path ya
    // arranca en m$x0, poner x='$padding' contaba el padding dos veces: el
    // texto empezaba 20px a la derecha del cursor y los ultimos 20px (dos
    // caracteres) se salian del path y no se dibujaban. Por eso aca solo se
    // pone x cuando hace falta anclar al medio.
    ?>
    <text class='anim' font-family='"<?= $font ?>", monospace' fill='<?= $color ?>' font-size='<?= $size ?>'
        dominant-baseline='<?= $vCenter ? "middle" : "auto" ?>'
        <?php if ($center): ?>x='50%' <?php endif; ?>text-anchor='<?= $center ? "middle" : "start" ?>'
        letter-spacing='<?= $letterSpacing ?>'><textPath xlink:href='#path<?= $i ?>'><?= $lines[$i]
        ?><?php if (!$terminal): ?><tspan class='cursor'>&#9608;</tspan><?php endif; ?></textPath></text>
<?php if ($terminal): ?>
    <?php
    // Cursor de terminal: uno solo por linea, siempre en el punto de insercion.
    // El <g> lo hace visible unicamente durante el ciclo de SU linea (opacity
    // vuelve a 0 al terminar la animacion), asi no se superponen los 21.
    ?>
    <g class='anim' opacity='0'><animate attributeName='opacity' begin='d<?= $i ?>.begin'
            dur='<?= round($total) ?>ms' values='1;1'
            fill='<?= $freeze ? "freeze" : "remove" ?>' />
        <text font-family='"<?= $font ?>", monospace' fill='<?= $color ?>' font-size='<?= $size ?>'
            dominant-baseline='<?= $vCenter ? "middle" : "auto" ?>'
            x='<?= $x0 ?>' y='<?= $yOffset ?>' text-anchor='start'>&#9608;<animate
                attributeName='x' begin='d<?= $i ?>.begin' calcMode='discrete'
                dur='<?= round($total) ?>ms' values='<?= implode(";", $cursorX) ?>'
                keyTimes='<?= implode(";", $keyTimes) ?>'
                fill='<?= $freeze ? "freeze" : "remove" ?>' /><animate
                attributeName='opacity' begin='d<?= $i ?>.begin' calcMode='discrete'
                dur='<?= round($total) ?>ms' values='<?= implode(";", $opValores) ?>'
                keyTimes='<?= implode(";", $opTiempos) ?>'
                fill='<?= $freeze ? "freeze" : "remove" ?>' /></text></g>
<?php endif; ?>
<?php endfor; ?>
    <?php
    // La linea quieta para quien pidio menos movimiento: la primera frase, sin
    // cursor y sin nada que se mueva. Va una sola vez, fuera del bucle.
    $yFijo = $multiline ? $size + 5 : $height / 2;
    ?>
    <text class='fijo' font-family='"<?= $font ?>", monospace' fill='<?= $color ?>' font-size='<?= $size ?>'
        dominant-baseline='<?= $vCenter ? "middle" : "auto" ?>'
        x='<?= $center ? "50%" : $padding ?>' y='<?= $yFijo ?>'
        text-anchor='<?= $center ? "middle" : "start" ?>'
        letter-spacing='<?= $letterSpacing ?>'><?= $lines[0] ?></text>
</svg>
