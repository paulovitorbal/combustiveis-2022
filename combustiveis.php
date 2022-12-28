<?php

function getFileName(string $name):string{
    return implode(
        DIRECTORY_SEPARATOR,
        [
            __DIR__,
            'data',
            $name
            ]

    );
}

function formatPtBrNumber(string $number):string{
    return str_replace(',', '.', $number);
}

$motolog = fopen(getFileName('motolog_csv_dobló_2022-12-27_192358.csv'), 'r');
$headers = fgetcsv($motolog);
$fuelConsumptions = [];
$start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2022-03-19 00:00:00');
while(($fields = fgetcsv($motolog)) !== false){
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $fields[0]);
    if ($date === false){
        throw new RuntimeException('something wrong happened: [' . implode(', ', $fields) . ']' );
    }
    if ($date < $start){
        continue;
    }
    $fuelConsumptions[$date->format('Y-m-d')] = [
        'date' => $date->format('Y-m-d'),
        'week' => (int) $date->format('oW'),
        'type' => $fields[11],
        'quantity' => $fields[12],
        'price' => $fields[14],
    ];
}
fclose($motolog);
ksort($fuelConsumptions);

$precosANP = fopen(getFileName('alcool-gasolina-df-2022.CSV'), 'r');
$precos = [];
$headers = fgetcsv($precosANP, null, ';');//skipping headers

while(($fields = fgetcsv($precosANP, null, ';')) !== false){

    if ($fields[5] === 'GASOLINA COMUM'){
        $type = 'pb';
    } else {
        $type = 'ethanol';
    }
    $date = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $fields[0] . ' 00:00:00');
    if ($date === false){
        throw new RuntimeException('something wrong happened: [' . implode(', ', $fields) . ']' );
    }
    $precos[$type][(int) $date->format('oW')] = [
        'date' => $date->format('Y-m-d'),
        'week' => (int) $date->format('oW'),
        'price' => [
            'min' => formatPtBrNumber($fields[10]),
            'max' => formatPtBrNumber($fields[11]),
            'avg' => formatPtBrNumber($fields[8]),
            'sd' => formatPtBrNumber($fields[9])
        ]
    ];
}
ksort($precos['ethanol']);
ksort($precos['pb']);
bcscale(3);
$results = [];
$cumulativo = [
    'base' => '0',
    'min' => '0',
    'max' => '0',
    'avg' => '0',
];
function getWeek(int $week, array $precos): int
{
    if (array_key_exists($week, $precos)){
        return $week;
    }
    return getWeek($week -1, $precos);
}

foreach($fuelConsumptions as $fuelConsumption){

    $week = getWeek((int) $fuelConsumption['week'], $precos[$fuelConsumption['type']]);
    $temp = $fuelConsumption;
    $totalAbastecimento = bcmul($fuelConsumption['quantity'], $fuelConsumption['price']);
    $temp['type'] = $fuelConsumption['type'];
    $temp['quantity'] = formatBr($temp['quantity']);
    $temp['price'] = formatBr($temp['price']);
    $temp['totalAbastecimento'] = formatBr($totalAbastecimento);
    $temp['precoSemanal'] = '----';
    $temp['precoMedio'] = formatBr($precos[$fuelConsumption['type']][$week]['price']['avg']);
    $temp['precoMinimo'] = formatBr($precos[$fuelConsumption['type']][$week]['price']['min']);
    $temp['precoMaximo'] = formatBr($precos[$fuelConsumption['type']][$week]['price']['max']);
    $temp['precoDesvioPadrao'] = formatBr($precos[$fuelConsumption['type']][$week]['price']['sd']);
    $temp['precoForaClube'] = '----';
    $foraClube['avg'] = bcmul($fuelConsumption['quantity'], $precos[$fuelConsumption['type']][$week]['price']['avg']);
    $temp['foraClubeUsandoMedio'] = formatBr($foraClube['avg']);
    $foraClube['min'] = bcmul($fuelConsumption['quantity'], $precos[$fuelConsumption['type']][$week]['price']['min']);
    $temp['foraClubeUsandoMinimo'] = formatBr($foraClube['min']);
    $foraClube['max'] = bcmul($fuelConsumption['quantity'], $precos[$fuelConsumption['type']][$week]['price']['max']);
    $temp['foraClubeUsandoMaximo'] = formatBr($foraClube['max']);
    $temp['totaisCumulativos'] = '----';
    $cumulativo['base'] = (bcadd($cumulativo['base'], $totalAbastecimento));
    $cumulativo['avg'] = (bcadd($cumulativo['avg'], $foraClube['avg']));
    $cumulativo['min'] = (bcadd($cumulativo['min'], $foraClube['min']));
    $cumulativo['max'] = (bcadd($cumulativo['max'], $foraClube['max']));
    $temp['totalBase'] = formatBr($cumulativo['base']);
    $temp['totalMedio'] = formatBr($cumulativo['avg']);
    $temp['totalMinimo'] = formatBr($cumulativo['min']);
    $temp['totalMaximo'] = formatBr($cumulativo['max']);
    $results[] = $temp;
}
var_dump($results);
function formatBr(string $number):string{
    return str_replace('.', ',', $number);
}
$outputExcel = fopen(getFileName('output_excel.csv'), 'w+');
$output = fopen(getFileName('output.csv'), 'w+');
fputcsv($outputExcel, array_keys($results[0]), ';');
foreach ($results as $line){
    fputcsv($outputExcel, $line, ';');
}
fclose($outputExcel);
fputcsv($output, array_keys($results[0]));
foreach ($results as $line){
    fputcsv($output, $line);
}
fclose($output);