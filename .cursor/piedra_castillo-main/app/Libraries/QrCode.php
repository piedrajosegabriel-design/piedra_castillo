<?php

namespace App\Libraries;

use InvalidArgumentException;

/* ============================================================
   QrCode
   QUÉ HACE: genera códigos QR desde cero, en PHP puro, y los
   devuelve como SVG listo para incrustar en una vista.

   POR QUÉ ESTÁ ESCRITO A MANO: el proyecto no usa Composer ni
   carga librerías por CDN (la página tiene que funcionar sin
   internet y sin dependencias externas). Así que el algoritmo
   del estándar ISO/IEC 18004 está implementado acá.

   ALCANCE: modo BYTE (sirve para cualquier texto ASCII/UTF-8),
   nivel de corrección de errores M (~15% del código puede
   estar tapado o roto y aun así se lee) y versiones 1 a 10
   (hasta 216 bytes de contenido). De sobra para el payload de
   WiFi que usa EdenAir:
       WIFI:T:WPA;S:EdenAir-Setup;P:xxxxxxxx;;

   CÓMO SE USA:
       $qr  = new \App\Libraries\QrCode('WIFI:T:WPA;S:...;;');
       $svg = $qr->toSvg(240);          // string con un <svg>

   SE RELACIONA CON: DevicePairingService (le pide el SVG del
   QR de vinculación) y la vista dispositivos/conectar.php.
   ============================================================ */
class QrCode
{
    // -------------------------------------------------------------------------
    // TABLAS DEL ESTÁNDAR
    // Son las mismas de la norma ISO/IEC 18004. No se calculan: están tabuladas.
    // -------------------------------------------------------------------------

    /**
     * Estructura de bloques para nivel de corrección M, versiones 1 a 10.
     *
     * Cada fila es [codewords de corrección por bloque,
     *               bloques del grupo 1, codewords de datos por bloque del grupo 1,
     *               bloques del grupo 2, codewords de datos por bloque del grupo 2].
     *
     * Un "codeword" es simplemente un byte del código.
     */
    private const BLOQUES_M = [
        1  => [10, 1, 16, 0, 0],
        2  => [16, 1, 28, 0, 0],
        3  => [26, 1, 44, 0, 0],
        4  => [18, 2, 32, 0, 0],
        5  => [24, 2, 43, 0, 0],
        6  => [16, 4, 27, 0, 0],
        7  => [18, 4, 31, 0, 0],
        8  => [22, 2, 38, 2, 39],
        9  => [22, 3, 36, 2, 37],
        10 => [26, 4, 43, 1, 44],
    ];

    /**
     * Centros de los patrones de alineación por versión (las "miras" chicas que
     * ayudan a corregir la deformación de la foto). La versión 1 no tiene.
     */
    private const ALINEACION = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /** Bits del nivel de corrección dentro de la información de formato. M = 00. */
    private const BITS_NIVEL_M = 0b00;

    // -------------------------------------------------------------------------
    // ESTADO DE LA INSTANCIA
    // -------------------------------------------------------------------------

    /** Texto que se codifica. */
    private string $texto;

    /** Versión del QR (1..10). Define el tamaño: 17 + 4*versión módulos de lado. */
    private int $version;

    /** Lado de la matriz en módulos (los "cuadraditos"). */
    private int $lado;

    /** Matriz final: $modulos[fila][columna] = true (negro) | false (blanco). */
    private array $modulos = [];

    /** Qué módulos son "de función" (no llevan datos): miras, timing, formato… */
    private array $reservado = [];

    /** Máscara elegida (0..7): la que menos penalización visual saca. */
    private int $mascara = 0;

    /** Tablas de logaritmos del campo de Galois GF(256). Se calculan una vez. */
    private static array $gfExp = [];
    private static array $gfLog = [];

    // -------------------------------------------------------------------------
    // CONSTRUCTOR — hace todo el trabajo
    // -------------------------------------------------------------------------

    public function __construct(string $texto)
    {
        if ($texto === '') {
            throw new InvalidArgumentException('El QR necesita un texto para codificar.');
        }

        $this->texto   = $texto;
        $this->version = $this->elegirVersion($texto);
        $this->lado    = 17 + 4 * $this->version;

        self::prepararGalois();

        // 1) Texto → bits → codewords de datos (con relleno).
        $datos = $this->armarCodewordsDeDatos();

        // 2) Datos → bloques + corrección de errores → secuencia final intercalada.
        $final = $this->intercalarConCorreccion($datos);

        // 3) Secuencia final → matriz de módulos (miras, timing, datos en zigzag).
        $this->dibujarMatriz($final);

        // 4) Probar las 8 máscaras y quedarse con la mejor.
        $this->aplicarMejorMascara();
    }

    // -------------------------------------------------------------------------
    // SALIDA
    // -------------------------------------------------------------------------

    /**
     * Devuelve el QR como SVG. `$tamano` es el lado en píxeles de la imagen
     * final; el margen (zona tranquila) de 4 módulos que exige la norma va
     * incluido, porque sin él muchos lectores no enganchan el código.
     */
    public function toSvg(int $tamano = 240, string $colorOscuro = '#0b1220', string $colorClaro = '#ffffff'): string
    {
        $margen = 4;
        $total  = $this->lado + $margen * 2;

        // Un solo <path> con todos los módulos oscuros: mucho más liviano que
        // pintar un <rect> por cuadradito.
        $path = '';
        for ($fila = 0; $fila < $this->lado; $fila++) {
            for ($col = 0; $col < $this->lado; $col++) {
                if ($this->modulos[$fila][$col]) {
                    $path .= 'M' . ($col + $margen) . ' ' . ($fila + $margen) . 'h1v1h-1z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $tamano . '" height="' . $tamano . '"'
            . ' viewBox="0 0 ' . $total . ' ' . $total . '" shape-rendering="crispEdges" role="img"'
            . ' aria-label="Código QR de vinculación">'
            . '<rect width="' . $total . '" height="' . $total . '" fill="' . $colorClaro . '"/>'
            . '<path d="' . $path . '" fill="' . $colorOscuro . '"/>'
            . '</svg>';
    }

    /** Matriz de módulos ya enmascarada. Se usa en los autotests. */
    public function matriz(): array
    {
        return $this->modulos;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function mascara(): int
    {
        return $this->mascara;
    }

    // -------------------------------------------------------------------------
    // PASO 1 — TEXTO A CODEWORDS DE DATOS
    // -------------------------------------------------------------------------

    /** Capacidad de datos (en bytes/codewords) de una versión en nivel M. */
    private static function capacidadDatos(int $version): int
    {
        [, $bloques1, $datos1, $bloques2, $datos2] = self::BLOQUES_M[$version];

        return $bloques1 * $datos1 + $bloques2 * $datos2;
    }

    /**
     * Busca la versión más chica donde entre el texto. El encabezado ocupa
     * 4 bits de modo + 8 bits de longitud (versiones 1..9) o 16 bits (10+).
     */
    private function elegirVersion(string $texto): int
    {
        $bytes = strlen($texto);

        foreach (array_keys(self::BLOQUES_M) as $version) {
            $bitsEncabezado = 4 + ($version >= 10 ? 16 : 8);
            $bitsNecesarios = $bitsEncabezado + $bytes * 8;

            if ($bitsNecesarios <= self::capacidadDatos($version) * 8) {
                return $version;
            }
        }

        throw new InvalidArgumentException(
            'El texto es demasiado largo para un QR de versión 10 (máximo 216 bytes).'
        );
    }

    /**
     * Arma la cadena de bits: modo byte (0100) + longitud + los bytes del texto
     * + terminador + relleno hasta completar la capacidad, y la convierte a
     * codewords (bytes).
     *
     * @return int[] codewords de datos
     */
    private function armarCodewordsDeDatos(): array
    {
        $capacidad = self::capacidadDatos($this->version);
        $bits      = '';

        // Modo BYTE.
        $bits .= '0100';

        // Longitud del contenido.
        $bitsLongitud = $this->version >= 10 ? 16 : 8;
        $bits .= str_pad(decbin(strlen($this->texto)), $bitsLongitud, '0', STR_PAD_LEFT);

        // Contenido.
        for ($i = 0, $n = strlen($this->texto); $i < $n; $i++) {
            $bits .= str_pad(decbin(ord($this->texto[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Terminador: hasta 4 ceros, si es que sobra lugar.
        $bits .= str_repeat('0', min(4, $capacidad * 8 - strlen($bits)));

        // Completar el último byte con ceros.
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - strlen($bits) % 8);
        }

        // Relleno alternando los dos bytes que fija la norma, hasta llenar.
        $relleno = [0xEC, 0x11];
        $i       = 0;
        while (strlen($bits) < $capacidad * 8) {
            $bits .= str_pad(decbin($relleno[$i % 2]), 8, '0', STR_PAD_LEFT);
            $i++;
        }

        $codewords = [];
        for ($i = 0; $i < $capacidad; $i++) {
            $codewords[] = bindec(substr($bits, $i * 8, 8));
        }

        return $codewords;
    }

    // -------------------------------------------------------------------------
    // PASO 2 — CORRECCIÓN DE ERRORES (REED-SOLOMON) E INTERCALADO
    // -------------------------------------------------------------------------

    /**
     * Prepara las tablas del campo de Galois GF(256) con el polinomio primitivo
     * 0x11D. Sirven para multiplicar bytes rápido: a*b = exp[log a + log b].
     */
    private static function prepararGalois(): void
    {
        if (self::$gfExp !== []) {
            return;
        }

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i]  = $x;
            self::$gfLog[$x]  = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        // Duplicado de la tabla para poder sumar exponentes sin hacer módulo.
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
    }

    /**
     * Polinomio generador para `$cantidad` codewords de corrección:
     * (x - a^0)(x - a^1)...(x - a^(n-1)) en GF(256).
     *
     * @return int[] coeficientes, del grado más alto al más bajo
     */
    private static function polinomioGenerador(int $cantidad): array
    {
        $g = [1];

        for ($i = 0; $i < $cantidad; $i++) {
            $nuevo = array_fill(0, count($g) + 1, 0);

            foreach ($g as $j => $coef) {
                // Parte "x * g"
                $nuevo[$j] ^= $coef;
                // Parte "a^i * g"
                if ($coef !== 0) {
                    $nuevo[$j + 1] ^= self::$gfExp[self::$gfLog[$coef] + $i];
                }
            }

            $g = $nuevo;
        }

        return $g;
    }

    /**
     * Calcula los codewords de corrección de un bloque: es el resto de dividir
     * el polinomio de datos por el generador.
     *
     * @param int[] $datos
     * @return int[]
     */
    private static function correccion(array $datos, int $cantidad): array
    {
        $g    = self::polinomioGenerador($cantidad);
        $resto = array_merge($datos, array_fill(0, $cantidad, 0));

        foreach ($datos as $i => $ignorado) {
            $coef = $resto[$i];

            if ($coef === 0) {
                continue;
            }

            $logCoef = self::$gfLog[$coef];

            for ($j = 1, $n = count($g); $j < $n; $j++) {
                if ($g[$j] !== 0) {
                    $resto[$i + $j] ^= self::$gfExp[self::$gfLog[$g[$j]] + $logCoef];
                }
            }
        }

        return array_slice($resto, count($datos));
    }

    /**
     * Parte los datos en bloques, le calcula la corrección a cada uno y arma la
     * secuencia final intercalando: primer codeword de cada bloque, segundo de
     * cada bloque, etc. (así una mancha en el papel no se come un bloque entero).
     *
     * @param int[] $datos
     * @return int[] secuencia completa de codewords lista para dibujar
     */
    private function intercalarConCorreccion(array $datos): array
    {
        [$ecPorBloque, $bloques1, $datos1, $bloques2, $datos2] = self::BLOQUES_M[$this->version];

        $bloquesDatos = [];
        $bloquesEc    = [];
        $offset       = 0;

        foreach ([[$bloques1, $datos1], [$bloques2, $datos2]] as [$cantidadBloques, $tamanoBloque]) {
            for ($b = 0; $b < $cantidadBloques; $b++) {
                $bloque         = array_slice($datos, $offset, $tamanoBloque);
                $offset        += $tamanoBloque;
                $bloquesDatos[] = $bloque;
                $bloquesEc[]    = self::correccion($bloque, $ecPorBloque);
            }
        }

        $final = [];

        // Intercalado de los codewords de datos.
        $maxDatos = max(array_map('count', $bloquesDatos));
        for ($i = 0; $i < $maxDatos; $i++) {
            foreach ($bloquesDatos as $bloque) {
                if (isset($bloque[$i])) {
                    $final[] = $bloque[$i];
                }
            }
        }

        // Intercalado de los codewords de corrección.
        for ($i = 0; $i < $ecPorBloque; $i++) {
            foreach ($bloquesEc as $bloque) {
                $final[] = $bloque[$i];
            }
        }

        return $final;
    }

    // -------------------------------------------------------------------------
    // PASO 3 — DIBUJO DE LA MATRIZ
    // -------------------------------------------------------------------------

    /** @param int[] $codewords */
    private function dibujarMatriz(array $codewords): void
    {
        $this->modulos   = array_fill(0, $this->lado, array_fill(0, $this->lado, false));
        $this->reservado = array_fill(0, $this->lado, array_fill(0, $this->lado, false));

        $this->dibujarMiras();
        $this->dibujarSeparadores();
        $this->dibujarAlineacion();
        $this->dibujarTiming();
        $this->reservarFormato();
        $this->dibujarModuloOscuro();

        $this->colocarDatos($codewords);
    }

    /** Las tres miras grandes de las esquinas (7x7). */
    private function dibujarMiras(): void
    {
        foreach ([[0, 0], [0, $this->lado - 7], [$this->lado - 7, 0]] as [$fila0, $col0]) {
            for ($f = 0; $f < 7; $f++) {
                for ($c = 0; $c < 7; $c++) {
                    $borde  = $f === 0 || $f === 6 || $c === 0 || $c === 6;
                    $centro = $f >= 2 && $f <= 4 && $c >= 2 && $c <= 4;

                    $this->modulos[$fila0 + $f][$col0 + $c]   = $borde || $centro;
                    $this->reservado[$fila0 + $f][$col0 + $c] = true;
                }
            }
        }
    }

    /**
     * El anillo blanco de 1 módulo que separa cada mira del resto del código.
     *
     * Se resuelve recorriendo la caja de 8x8 que envuelve a cada mira de 7x7:
     * lo que está dentro de la caja y no es mira, es separador. Ojo con
     * agrandar la caja: un módulo de más deja zonas blancas donde en realidad
     * tienen que ir datos, y el QR sale ilegible.
     */
    private function dibujarSeparadores(): void
    {
        $cajas = [
            [0, 0],                                 // mira superior izquierda
            [0, $this->lado - 8],                   // mira superior derecha
            [$this->lado - 8, 0],                   // mira inferior izquierda
        ];

        foreach ($cajas as [$fila0, $col0]) {
            for ($f = 0; $f < 8; $f++) {
                for ($c = 0; $c < 8; $c++) {
                    $fila = $fila0 + $f;
                    $col  = $col0 + $c;

                    if ($this->reservado[$fila][$col]) {
                        continue; // es la mira, ya está dibujada
                    }

                    $this->modulos[$fila][$col]   = false;
                    $this->reservado[$fila][$col] = true;
                }
            }
        }
    }

    /** Las miras chicas (5x5) que evitan que el código se lea deformado. */
    private function dibujarAlineacion(): void
    {
        $centros = self::ALINEACION[$this->version];

        foreach ($centros as $filaCentro) {
            foreach ($centros as $colCentro) {
                // Las tres esquinas ya tienen mira grande: ahí no va ninguna chica.
                if ($this->chocaConMira($filaCentro, $colCentro)) {
                    continue;
                }

                for ($f = -2; $f <= 2; $f++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $borde  = abs($f) === 2 || abs($c) === 2;
                        $centro = $f === 0 && $c === 0;

                        $this->modulos[$filaCentro + $f][$colCentro + $c]   = $borde || $centro;
                        $this->reservado[$filaCentro + $f][$colCentro + $c] = true;
                    }
                }
            }
        }
    }

    private function chocaConMira(int $fila, int $col): bool
    {
        $limite = $this->lado - 8;

        return ($fila <= 8 && $col <= 8)
            || ($fila <= 8 && $col >= $limite)
            || ($fila >= $limite && $col <= 8);
    }

    /** Las líneas punteadas (fila 6 y columna 6) que dan la escala al lector. */
    private function dibujarTiming(): void
    {
        for ($i = 8; $i < $this->lado - 8; $i++) {
            $valor = $i % 2 === 0;

            $this->modulos[6][$i]   = $valor;
            $this->reservado[6][$i] = true;

            $this->modulos[$i][6]   = $valor;
            $this->reservado[$i][6] = true;
        }
    }

    /** Reserva las franjas donde después va la información de formato y versión. */
    private function reservarFormato(): void
    {
        for ($i = 0; $i < 9; $i++) {
            if (! $this->reservado[8][$i]) {
                $this->reservado[8][$i] = true;
            }
            if (! $this->reservado[$i][8]) {
                $this->reservado[$i][8] = true;
            }
        }

        for ($i = 0; $i < 8; $i++) {
            $this->reservado[8][$this->lado - 1 - $i] = true;
            $this->reservado[$this->lado - 1 - $i][8] = true;
        }

        // Bloques de información de versión (solo de la versión 7 en adelante).
        if ($this->version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $this->reservado[$i][$this->lado - 11 + $j] = true;
                    $this->reservado[$this->lado - 11 + $j][$i] = true;
                }
            }
        }
    }

    /** El módulo siempre negro que exige la norma, abajo a la izquierda. */
    private function dibujarModuloOscuro(): void
    {
        $this->modulos[$this->lado - 8][8]   = true;
        $this->reservado[$this->lado - 8][8] = true;
    }

    /**
     * Recorre la matriz en zigzag (de a dos columnas, de derecha a izquierda,
     * subiendo y bajando) y va soltando los bits de los codewords en los
     * módulos que no son de función.
     *
     * @param int[] $codewords
     */
    private function colocarDatos(array $codewords): void
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $indice  = 0;
        $totales = strlen($bits);
        $subiendo = true;

        for ($colDerecha = $this->lado - 1; $colDerecha > 0; $colDerecha -= 2) {
            // La columna 6 es la del timing vertical: se saltea entera.
            if ($colDerecha === 6) {
                $colDerecha = 5;
            }

            for ($paso = 0; $paso < $this->lado; $paso++) {
                $fila = $subiendo ? $this->lado - 1 - $paso : $paso;

                foreach ([$colDerecha, $colDerecha - 1] as $col) {
                    if ($this->reservado[$fila][$col]) {
                        continue;
                    }

                    // Si se acabaron los bits, quedan en blanco (la norma lo permite).
                    $this->modulos[$fila][$col] = $indice < $totales && $bits[$indice] === '1';
                    $indice++;
                }
            }

            $subiendo = ! $subiendo;
        }
    }

    // -------------------------------------------------------------------------
    // PASO 4 — MÁSCARAS
    // -------------------------------------------------------------------------

    /**
     * Aplica cada una de las 8 máscaras y se queda con la que menos
     * "penalización" acumula. La máscara evita que queden zonas grandes de un
     * solo color o dibujos parecidos a las miras, que confundirían al lector.
     */
    private function aplicarMejorMascara(): void
    {
        $original = $this->modulos;
        $mejor    = null;
        $mejorPen = PHP_INT_MAX;

        for ($mascara = 0; $mascara < 8; $mascara++) {
            $this->modulos = $original;
            $this->enmascarar($mascara);
            $this->escribirFormato($mascara);

            if ($this->version >= 7) {
                $this->escribirVersion();
            }

            $penalizacion = $this->penalizacion();

            if ($penalizacion < $mejorPen) {
                $mejorPen      = $penalizacion;
                $mejor         = $this->modulos;
                $this->mascara = $mascara;
            }
        }

        $this->modulos = $mejor;
    }

    /** Invierte los módulos de datos según la fórmula de la máscara elegida. */
    private function enmascarar(int $mascara): void
    {
        for ($fila = 0; $fila < $this->lado; $fila++) {
            for ($col = 0; $col < $this->lado; $col++) {
                if ($this->reservado[$fila][$col]) {
                    continue;
                }

                if (self::condicionMascara($mascara, $fila, $col)) {
                    $this->modulos[$fila][$col] = ! $this->modulos[$fila][$col];
                }
            }
        }
    }

    /** Las 8 fórmulas de máscara del estándar. */
    private static function condicionMascara(int $mascara, int $i, int $j): bool
    {
        return match ($mascara) {
            0 => ($i + $j) % 2 === 0,
            1 => $i % 2 === 0,
            2 => $j % 3 === 0,
            3 => ($i + $j) % 3 === 0,
            4 => (intdiv($i, 2) + intdiv($j, 3)) % 2 === 0,
            5 => ($i * $j) % 2 + ($i * $j) % 3 === 0,
            6 => (($i * $j) % 2 + ($i * $j) % 3) % 2 === 0,
            7 => ((($i + $j) % 2) + ($i * $j) % 3) % 2 === 0,
        };
    }

    /**
     * Escribe los 15 bits de información de formato (nivel de corrección +
     * máscara + BCH de control) en sus dos ubicaciones.
     */
    private function escribirFormato(int $mascara): void
    {
        $datos = (self::BITS_NIVEL_M << 3) | $mascara;

        // BCH(15,5): resto de dividir por el polinomio generador 0x537.
        $resto = $datos;
        for ($i = 0; $i < 10; $i++) {
            $resto = ($resto << 1) ^ ((($resto >> 9) & 1) * 0x537);
        }

        $bits = (($datos << 10) | $resto) ^ 0x5412;

        // Copia 1 — franja vertical (columna 8, de arriba hacia abajo) y
        // franja horizontal (fila 8, de derecha hacia izquierda).
        // El bit 0 vive en [fila 0][columna 8] y el bit 14 en [fila 8][columna 0].
        for ($i = 0; $i <= 5; $i++) {
            $this->modulos[$i][8] = (bool) (($bits >> $i) & 1);
        }
        $this->modulos[7][8] = (bool) (($bits >> 6) & 1);
        $this->modulos[8][8] = (bool) (($bits >> 7) & 1);
        $this->modulos[8][7] = (bool) (($bits >> 8) & 1);
        for ($i = 9; $i <= 14; $i++) {
            $this->modulos[8][14 - $i] = (bool) (($bits >> $i) & 1);
        }

        // Copia 2 — se reparte entre el borde derecho de la fila 8 y el borde
        // inferior de la columna 8, para que el lector la encuentre igual si
        // una esquina quedó tapada.
        for ($i = 0; $i <= 7; $i++) {
            $this->modulos[8][$this->lado - 1 - $i] = (bool) (($bits >> $i) & 1);
        }
        for ($i = 8; $i <= 14; $i++) {
            $this->modulos[$this->lado - 15 + $i][8] = (bool) (($bits >> $i) & 1);
        }

        // El módulo oscuro nunca cambia.
        $this->modulos[$this->lado - 8][8] = true;
    }

    /** Los 18 bits de información de versión (solo versiones 7 a 40). */
    private function escribirVersion(): void
    {
        $resto = $this->version;
        for ($i = 0; $i < 12; $i++) {
            $resto = ($resto << 1) ^ ((($resto >> 11) & 1) * 0x1F25);
        }

        $bits = ($this->version << 12) | $resto;

        for ($i = 0; $i < 18; $i++) {
            $valor = (bool) (($bits >> $i) & 1);
            $fila  = intdiv($i, 3);
            $col   = $i % 3;

            $this->modulos[$fila][$this->lado - 11 + $col] = $valor;
            $this->modulos[$this->lado - 11 + $col][$fila] = $valor;
        }
    }

    // -------------------------------------------------------------------------
    // PENALIZACIÓN (las 4 reglas que deciden la mejor máscara)
    // -------------------------------------------------------------------------

    private function penalizacion(): int
    {
        return $this->penalizacionRachas()
            + $this->penalizacionBloques()
            + $this->penalizacionFalsasMiras()
            + $this->penalizacionProporcion();
    }

    /** Regla 1: 5 o más módulos seguidos del mismo color. */
    private function penalizacionRachas(): int
    {
        $total = 0;

        for ($i = 0; $i < $this->lado; $i++) {
            foreach ([true, false] as $porFilas) {
                $anterior = null;
                $racha    = 0;

                for ($j = 0; $j < $this->lado; $j++) {
                    $valor = $porFilas ? $this->modulos[$i][$j] : $this->modulos[$j][$i];

                    if ($valor === $anterior) {
                        $racha++;
                    } else {
                        $anterior = $valor;
                        $racha    = 1;
                    }

                    if ($racha === 5) {
                        $total += 3;
                    } elseif ($racha > 5) {
                        $total += 1;
                    }
                }
            }
        }

        return $total;
    }

    /** Regla 2: cada cuadrado 2x2 de un solo color. */
    private function penalizacionBloques(): int
    {
        $total = 0;

        for ($fila = 0; $fila < $this->lado - 1; $fila++) {
            for ($col = 0; $col < $this->lado - 1; $col++) {
                $v = $this->modulos[$fila][$col];

                if ($v === $this->modulos[$fila][$col + 1]
                    && $v === $this->modulos[$fila + 1][$col]
                    && $v === $this->modulos[$fila + 1][$col + 1]) {
                    $total += 3;
                }
            }
        }

        return $total;
    }

    /** Regla 3: dibujos que imitan a una mira (1:1:3:1:1 con 4 claros al lado). */
    private function penalizacionFalsasMiras(): int
    {
        $patrones = ['10111010000', '00001011101'];
        $total    = 0;

        for ($i = 0; $i < $this->lado; $i++) {
            $fila = '';
            $col  = '';

            for ($j = 0; $j < $this->lado; $j++) {
                $fila .= $this->modulos[$i][$j] ? '1' : '0';
                $col  .= $this->modulos[$j][$i] ? '1' : '0';
            }

            foreach ($patrones as $patron) {
                $total += substr_count($fila, $patron) * 40;
                $total += substr_count($col, $patron) * 40;
            }
        }

        return $total;
    }

    /** Regla 4: qué tan lejos del 50% está la proporción de módulos oscuros. */
    private function penalizacionProporcion(): int
    {
        $oscuros = 0;

        foreach ($this->modulos as $fila) {
            foreach ($fila as $valor) {
                if ($valor) {
                    $oscuros++;
                }
            }
        }

        $porcentaje = $oscuros * 100 / ($this->lado * $this->lado);

        return (int) (floor(abs($porcentaje - 50) / 5) * 10);
    }
}

/* ============================================================================
   GLOSARIO DE MÉTODOS DE ESTE ARCHIVO

   Uso público:
   - __construct($texto)  → codifica el texto y deja la matriz lista
   - toSvg($tamano, ...)  → devuelve el QR como <svg> incrustable
   - matriz()/version()/mascara() → introspección (los usan los autotests)

   Paso 1 — texto a bytes:
   - capacidadDatos($v)          → cuántos bytes de datos entran en esa versión
   - elegirVersion($texto)       → la versión más chica donde entra el texto
   - armarCodewordsDeDatos()     → modo + longitud + texto + relleno → bytes

   Paso 2 — corrección de errores:
   - prepararGalois()            → tablas exp/log de GF(256) (polinomio 0x11D)
   - polinomioGenerador($n)      → (x-a^0)...(x-a^(n-1))
   - correccion($datos, $n)      → resto de la división = codewords de control
   - intercalarConCorreccion()   → parte en bloques e intercala la salida

   Paso 3 — dibujo:
   - dibujarMatriz()             → orquesta todo el dibujo
   - dibujarMiras()/dibujarSeparadores()/dibujarAlineacion()/dibujarTiming()
   - reservarFormato()/dibujarModuloOscuro() → zonas que no llevan datos
   - colocarDatos($codewords)    → recorrido en zigzag soltando los bits

   Paso 4 — máscaras:
   - aplicarMejorMascara()       → prueba las 8 y elige la de menor penalización
   - enmascarar($m)/condicionMascara($m,$i,$j) → invierte según la fórmula
   - escribirFormato($m)/escribirVersion()     → metadatos con BCH de control
   - penalizacion() y sus 4 reglas             → puntaje visual de cada máscara

   Conceptos:
   - módulo    → cada cuadradito del QR
   - codeword  → un byte del código (de datos o de corrección)
   - GF(256)   → aritmética de bytes que usa Reed-Solomon
   - nivel M   → tolera perder ~15% del código y aun así leerlo
   ============================================================================ */
