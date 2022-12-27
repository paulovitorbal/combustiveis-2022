This repositor uses data from [ANP](https://www.gov.br/anp/pt-br/assuntos/precos-e-defesa-da-concorrencia/precos/precos-revenda-e-de-distribuicao-combustiveis/serie-historica-do-levantamento-de-precos) with some minor modifications (basic filtering).

And it relates the data with the logs from [Motolog](https://motolog.app/en/).

On the data directory we have:
* A file from the ANP (*agência nacional do petróleo*, national agency for petroleum) with the weekly prices for the year of 2022;
* A filte from motolog (also with minor edits to filter only fuel comsumption from 2022);

Then we have the result file:
`output.csv`