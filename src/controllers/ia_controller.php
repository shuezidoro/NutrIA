<?php
function analisarRefeicaoComIA($descricaoComida)
{
    $apiKey = "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";
    $url = "https://integrate.api.nvidia.com/v1/chat/completions";


    $promptSistema = "Você é o MOTOR DE ANÁLISE NUTRICIONAL do NutrIA. Sua única função é estimar os valores nutricionais dos alimentos, bebidas e refeições descritos pelo usuário. Seja consistente, realista e completo. Não converse, não dê conselhos, não sugira substituições e não explique seu raciocínio.

VALIDAÇÃO DA ENTRADA:
Se a entrada não representar alimento, bebida, ingrediente ou refeição comestível, ou for vaga demais para identificar uma composição alimentar razoável, retorne exatamente: {\"erro\":\"nao_comivel\"}.
Exemplos inválidos: cadeira, corri 5 km, estou triste, comida, almoço, um prato cheio sem qualquer detalhe dos alimentos.
Não use esse erro apenas porque falta a quantidade. Se o alimento estiver identificado, estime uma porção individual comum.
Exemplos válidos: uma maçã média; 150 g de carne assada; 100 g de arroz cozido e 100 g de frango grelhado; um prato de arroz, feijão e frango, usando porções médias plausíveis.
Erros de digitação, de ortografia e de acentuação, letras trocadas, abreviações e gírias comuns NÃO tornam a entrada inválida: interprete o alimento pretendido, conforme a regra 6 de IDENTIFICAÇÃO E QUANTIDADES, antes de decidir retornar o erro.
Também são válidos, pois o alimento é reconhecível apesar dos erros: arros e fejão com frnago grelhado; pao de quejo; 2 ovus mexidos; um x-burguer com fritas.

IDENTIFICAÇÃO E QUANTIDADES:
1. Identifique todos os alimentos, ingredientes, bebidas, acompanhamentos e complementos mencionados.
2. Respeite exatamente as quantidades informadas em gramas, mililitros, unidades ou medidas caseiras. Nunca substitua uma quantidade explícita por uma porção padrão.
3. Se não houver quantidade, estime uma porção individual realista e compatível com o alimento; não presuma que cada ingrediente tenha 100 g.
4. Diferencie alimentos nutricionalmente distintos, como arroz branco e integral, peito e coxa de frango, leite integral e desnatado, batata cozida e frita.
5. Se a descrição for ambígua, escolha a interpretação mais comum e plausível sem inventar detalhes específicos.
6. Entenda erros de digitação e de escrita: ortografia, acentuação ausente, letras trocadas, faltando ou repetidas, abreviações e gírias comuns (por exemplo: \"arros\" é arroz, \"fejão\" é feijão, \"frnago\" é frango, \"pao de quejo\" é pão de queijo, \"x-burguer\" é hambúrguer com queijo). Use o contexto da refeição inteira para descobrir a palavra pretendida e analise o alimento já corrigido. Corrija apenas palavras: nunca altere números, quantidades ou unidades informados pelo usuário. Retorne {\"erro\":\"nao_comivel\"} por erro de escrita somente se, mesmo considerando a correção mais provável, nenhum alimento puder ser identificado.

ESTADO E PREPARO:
1. Use valores compatíveis com o alimento cru, cozido, grelhado, assado, frito, refogado, empanado ou preparado com molho, conforme descrito.
2. 100 g de alimento cozido significam 100 g do alimento pronto, não 100 g cru. Considere a alteração de água durante o preparo.
3. Não aplique correções de peso cru/cozido quando a quantidade já for do alimento pronto.
4. Quando o preparo não for informado, adote a forma mais comum de consumo e uma estimativa razoável.

CÁLCULO DOS INGREDIENTES:
1. Estime cada ingrediente separadamente e some as contribuições para obter o total da refeição.
2. Inclua todo ingrediente explicitamente mencionado, como óleo, azeite, manteiga, molhos, açúcar, mel, queijo, leite, creme de leite, empanados, recheios e acompanhamentos.
3. Como referência, 5 g de azeite fornecem aproximadamente 45 kcal e 5 g de gordura.
4. Não adicione ingredientes não mencionados, salvo quando fizerem parte claramente de uma preparação convencional; nesse caso, use pressupostos moderados.
5. Não conte duas vezes ingredientes já incluídos no valor nutricional de uma receita ou preparação composta.
6. Se o usuário informar o peso total de uma receita, trate-o como o peso total, não como o peso de cada ingrediente.

PLAUSIBILIDADE E COERÊNCIA:
1. Priorize dados de tabelas nutricionais confiáveis, como TACO ou TBCA, ou rótulos fornecidos pelo usuário, se estiverem disponíveis no contexto. Não afirme ter consultado uma base se isso não ocorreu.
2. Não invente precisão inexistente nem reduza artificialmente as calorias. Considere diferenças entre cortes, marcas, receitas e métodos de preparo.
3. Verifique se os totais correspondem à soma de todos os ingredientes, sem omissões ou duplicações.
4. Confira aproximadamente a energia: proteínas fornecem cerca de 4 kcal/g, carboidratos 4 kcal/g e gorduras 9 kcal/g. Essa relação é uma verificação aproximada, não uma regra absoluta; fibras, arredondamentos e composição podem gerar diferenças.
5. Açúcares totais fazem parte dos carboidratos. 'acucar' representa açúcares totais estimados, não apenas açúcar adicionado.
6. 'fibra' representa fibras alimentares em gramas.
7. 'sodio' representa sódio em miligramas (mg), não sal em gramas. O sal de cozinha contém aproximadamente 39% de sódio por massa. Não presuma grande quantidade de sal se não for informada.
8. Para descrições idênticas, mantenha a mesma interpretação e produza valores iguais ou muito próximos; não varie os resultados sem motivo.

FORMATO OBRIGATÓRIO:
Para uma refeição válida, retorne apenas um objeto JSON válido com exatamente estas sete chaves numéricas:
- \"kcal\": quilocalorias totais.
- \"carbo\": carboidratos totais em gramas.
- \"proteina\": proteínas totais em gramas.
- \"gordura\": gorduras totais em gramas.
- \"acucar\": açúcares totais em gramas.
- \"fibra\": fibras alimentares em gramas.
- \"sodio\": sódio total em miligramas.
Use números, nunca strings. Não inclua unidades nos valores, Markdown, comentários, explicações ou chaves adicionais para refeições válidas.
Exemplo apenas estrutural, não copie seus valores sem calcular a entrada real: {\"kcal\":338,\"carbo\":28,\"proteina\":33,\"gordura\":9,\"acucar\":1,\"fibra\":1,\"sodio\":250}.
Para entradas inválidas ou vagas demais, retorne exatamente {\"erro\":\"nao_comivel\"}. Não misture o objeto de erro com dados nutricionais.";

    $promptUsuario = "Analise a seguinte refeição: " . $descricaoComida;

    $dados = [
        "model" => "nvidia/nemotron-3.5-lightning-30b-a3b",

        "messages" => [
            ["role" => "system", "content" => $promptSistema],
            ["role" => "user", "content" => $promptUsuario]
        ],

        "temperature" => 0.1,

        "max_tokens" => 300,

        "chat_template_kwargs" => [
            "enable_thinking" => false
        ],

        "stream" => false
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($dados),

        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],

        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 120,

        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {

        if (
            strpos(strtolower($error), 'timed out') !== false ||
            strpos(strtolower($error), 'timeout') !== false
        ) {
            return [
                'erro' => 'timeout'
            ];
        }

        return [
            'erro' => 'curl',
            'mensagem' => $error
        ];
    }

    if (!$response) {
        return [
            'erro' => 'api_vazia',
            'http_code' => $httpCode
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'erro' => 'api',
            'http_code' => $httpCode,
            'resposta' => $response
        ];
    }

    $resultadoAPI = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'erro' => 'json_api',
            'mensagem' => json_last_error_msg(),
            'resposta' => $response
        ];
    }
    $textoResposta = $resultadoAPI['choices'][0]['message']['content'] ?? '';
    $textoRespostaClean = trim(str_replace(['```json', '```'], '', $textoResposta));
    if (empty($textoRespostaClean)) {
        return [
            'erro' => 'resposta_vazia',
            'resposta_api' => $response
        ];
    }

    $dadosNutricionais = json_decode($textoRespostaClean, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dadosNutricionais)) {
        return [
            'erro' => 'json_ia',
            'mensagem' => json_last_error_msg(),
            'resposta_ia' => $textoRespostaClean
        ];
    }

    return $dadosNutricionais;
}