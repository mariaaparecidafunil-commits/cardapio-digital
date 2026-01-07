<?php
// public_html/finalizar.php
session_start();
require_once __DIR__ . '/backend/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: index.php");
  exit;
}

$nome = $_POST['nome'] ?? '';
$endereco = $_POST['endereco'] ?? '';
$obs = $_POST['obs'] ?? '';
$pagamento = $_POST['pagamento'] ?? '';
$carrinho = json_decode($_POST['carrinho'] ?? '[]', true);

if (!$nome || !$endereco || !$pagamento || empty($carrinho)) {
  die("⚠️ Dados inválidos do pedido.");
}

// Inserir pedido
$total = 0;
foreach ($carrinho as $item) {
  $total += $item['preco'] * $item['qtd'];
}

$status = 'recebido';
$tempoEstimado = "30 minutos";

$stmt = $conn->prepare("INSERT INTO pedidos (cliente_nome, cliente_end, obs, pagamento, total, status, tempo_estimado, data) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssiss", $nome, $endereco, $obs, $pagamento, $total, $status, $tempoEstimado);
$stmt->execute();
$pedidoId = $stmt->insert_id;
$stmt->close();

// Inserir itens
$stmt = $conn->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, qtd, preco) VALUES (?, ?, ?, ?)");
foreach ($carrinho as $item) {
  $id = intval($item['id']);
  $qtd = intval($item['qtd']);
  $preco = floatval($item['preco']);
  $stmt->bind_param("iiid", $pedidoId, $id, $qtd, $preco);
  $stmt->execute();
}
$stmt->close();

// Montar mensagem para WhatsApp
$msg = "*Novo Pedido - Mimoso Lanches*%0A";
$msg .= "📌 Pedido #$pedidoId%0A";
$msg .= "👤 Cliente: $nome%0A";
$msg .= "🏠 Endereço: $endereco%0A";
if ($obs) $msg .= "📝 Obs: $obs%0A";
$msg .= "💳 Pagamento: $pagamento%0A%0A";
$msg .= "*Itens:*%0A";

foreach ($carrinho as $item) {
  $linha = $item['qtd']."x ".$item['nome']." - R$ ".number_format($item['preco']*$item['qtd'],2,",",".");
  $msg .= $linha."%0A";
}
$msg .= "%0A*Total: R$ ".number_format($total,2,",",".")."*%0A%0A";
$msg .= "📲 Acompanhe: https://mimosolanches.com.br/meu-pedido.php?id=$pedidoId";

// Número do WhatsApp
$whats = "5528999652545";

// Redirecionar
header("Location: https://wa.me/$whats?text=$msg");
exit;
?>
