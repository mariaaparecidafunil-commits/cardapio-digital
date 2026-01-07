<?php
// ===============================================
// 🔧 DEBUG MODE — MOSTRAR ERROS EM TELA
// ===============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('America/Sao_Paulo');

// ===============================================
// 🔌 CONEXÃO COM BANCO (raiz/public_html/backend/conexao.php)
// ===============================================
require_once __DIR__ . '/backend/conexao.php';
if (!isset($conn) || !$conn) {
    die("<b>Erro:</b> conexão não estabelecida. Verifique <code>/backend/conexao.php</code>.");
}

// ===============================================
// ⏰ HORÁRIO DE FUNCIONAMENTO
// ===============================================
$hora = date('H:i');
$aberto = ($hora >= "10:00" && $hora <= "22:50");

// ===============================================
// 📂 CATEGORIAS
// ===============================================
$resCat = $conn->query("SELECT * FROM categorias ORDER BY nome ASC");
if (!$resCat) die("<b>Erro SQL categorias:</b> " . $conn->error);
$categorias = $resCat->fetch_all(MYSQLI_ASSOC);

// ===============================================
// 📦 PRODUTOS (com nome da categoria)
// ===============================================
$resProd = $conn->query("
  SELECT p.*, c.nome AS categoria_nome
  FROM produtos p
  LEFT JOIN categorias c ON c.id = p.categoria_id
  ORDER BY c.nome, p.nome
");
if (!$resProd) die("<b>Erro SQL produtos:</b> " . $conn->error);
$produtos = $resProd->fetch_all(MYSQLI_ASSOC);

// ===============================================
// ➕ OPCIONAIS PAGOS (agrupados por produto_id)
// ===============================================
// ➕ OPCIONAIS PAGOS (agrupados por produto_id)
$sqlOpc = "
  SELECT po.produto_id, o.id AS opcional_id, o.nome, o.preco
  FROM produto_opcional po
  JOIN opcionais o ON o.id = po.opcional_id
  ORDER BY o.nome ASC
";
$resOpc = $conn->query($sqlOpc);
if (!$resOpc) die('<b>Erro SQL opcionais:</b> ' . $conn->error);
$opcionais = [];
while($o = $resOpc->fetch_assoc()){
  $opcionais[$o['produto_id']][] = ['id'=>$o['opcional_id'], 'nome'=>$o['nome'], 'preco'=>$o['preco']];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Mimoso Lanches - Cardápio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {background:#111827;color:#f9fafb;font-family:Arial,sans-serif;}
    .card img {height:160px;width:100%;object-fit:cover;border-radius:10px;transition:transform .3s}
    .card img:hover {transform: scale(1.05);}
    #carrinho {position:fixed;top:0;right:-400px;width:400px;max-width:100%;height:100%;
      background:#1f2937;color:#f9fafb;border-left:3px solid #ff6b00;box-shadow:-3px 0 6px rgba(0,0,0,0.2);
      padding:20px;transition:right .3s ease;z-index:1050;overflow-y:auto;border-radius:12px 0 0 12px;}
    #carrinho.ativo { right:0; }
    footer {margin-top:40px;padding:20px;text-align:center;background:#1f2937;color:#f9fafb;font-size:14px;}
    #btnCarrinhoFlutuante {position: fixed;bottom: 20px;right: 20px;background:#ff6b00;color:#fff;
      border:none;border-radius:50%;width:60px;height:60px;font-size:22px;box-shadow:0 4px 8px rgba(0,0,0,.3);
      cursor:pointer;z-index:1100;}
    #contadorFlutuante {background:#dc2626;color:#fff;border-radius:50%;padding:2px 6px;font-size:12px;margin-left:4px;}
    .categorias-menu {display:flex;gap:10px;overflow-x:auto;padding:10px;background:#374151;border-radius:8px;}
    .categorias-menu a {text-decoration:none;padding:6px 12px;background:#ff6b00;color:#fff;
      border-radius:6px;white-space:nowrap;transition:background .2s;}
    .categorias-menu a:hover {background:#e65c00;}
    .categoria-titulo {background:linear-gradient(90deg,#ff6b00 0%,#ff8533 100%);
      color:#fff;padding:8px 12px;border-radius:8px;font-size:1.2rem;display:flex;align-items:center;gap:6px;margin-bottom:15px;}
    .categoria-icone {font-size:1.4rem;}
    .bg-section-1{background:#1f2937;} .bg-section-2{background:#111827;}
    .bg-section-3{background:#1e293b;} .bg-section-4{background:#0f172a;}
    .categoria-bloco {margin-bottom:30px;padding:15px;border-radius:10px;}
    .card-text.small.text-muted{color:#d1d5db!important;font-size:13px;}
    .fly{position:fixed;z-index:2000;transition:all .7s ease-in-out;pointer-events:none;}
    #modalOpc{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);
      display:flex;align-items:center;justify-content:center;z-index:2000;}
    #modalOpc>div{background:#fff;color:#000;padding:18px;border-radius:10px;max-width:420px;width:90%;}
    #modalOpc .modal-actions{display:grid;gap:8px;margin-top:10px}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg" style="background:#ff6b00;">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="#">🍔 Mimoso Lanches</a>
    <span class="text-white">📞 WhatsApp: (28) 99965-2545</span>
    <button class="btn btn-light ms-3" onclick="toggleCarrinho()">🛒 Carrinho <span class="badge bg-dark" id="contador">0</span></button>
  </div>
</nav>

<div class="container mt-3">
  <div class="categorias-menu">
    <?php foreach($categorias as $c): ?>
      <a href="#cat<?=$c['id']?>"><?=htmlspecialchars($c['nome'])?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="container mt-4">
<?php $bgClasses=['bg-section-1','bg-section-2','bg-section-3','bg-section-4'];$bgIndex=0;?>
<?php foreach($categorias as $c): ?>
  <?php $bgClass=$bgClasses[$bgIndex%count($bgClasses)];$bgIndex++; ?>
  <div class="categoria-bloco <?=$bgClass?>" id="cat<?=$c['id']?>">
    <h4 class="categoria-titulo">
      <span class="categoria-icone">
        <?php $nome=strtolower($c['nome']);
          if(strpos($nome,'lanche')!==false) echo "🍔";
          elseif(strpos($nome,'bebida')!==false) echo "🥤";
          elseif(strpos($nome,'pizza')!==false) echo "🍕";
          elseif(strpos($nome,'combo')!==false) echo "🥡";
          elseif(strpos($nome,'sobremesa')!==false) echo "🍨";
          else echo "🍽️";
        ?>
      </span>
      <?=htmlspecialchars($c['nome'])?>
    </h4>
    <div class="row g-3">
      <?php foreach($produtos as $p): if($p['categoria_id']==$c['id']): ?>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm" style="background:#1f2937;color:#f9fafb;">
          <?php if(!empty($p['imagem'])): ?>
            <img src="uploads/produtos/<?=htmlspecialchars($p['imagem'])?>" class="card-img-top produto-img" alt="<?=htmlspecialchars($p['nome'])?>">
          <?php else: ?>
            <img src="img/sem-foto.jpg" class="card-img-top produto-img" alt="<?=htmlspecialchars($p['nome'])?>">
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?=htmlspecialchars($p['nome'])?></h5>
            <?php if(!empty(trim($p['ingredientes'] ?? ''))): ?>
             <p class="card-text small text-muted">✔ <?=htmlspecialchars($p['ingredientes'])?></p>
            <?php endif; ?>

            <div class="mt-auto">
              <?php if (stripos($p['categoria_nome'], 'bebida') !== false): ?>
                <button class="btn btn-sm btn-success w-100 mb-1"
                        onclick="adicionarComOpcionais(<?=$p['id']?>,'<?=htmlspecialchars($p['nome'], ENT_QUOTES)?>',<?=$p['preco_industrial']?>, this, true)">
                  <?=$p['nome']?> - R$ <?=number_format($p['preco_industrial'],2,',','.')?>
                </button>
              <?php else: ?>
                <?php if($p['preco_industrial']>0): ?>
                  <button class="btn btn-sm btn-primary w-100 mb-1"
                          onclick="adicionarComOpcionais(<?=$p['id']?>,'<?=htmlspecialchars($p['nome'], ENT_QUOTES)?> - Carne Industrial',<?=$p['preco_industrial']?>, this, false)">
                    Carne Industrial - R$ <?=number_format($p['preco_industrial'],2,',','.')?>
                  </button>
                <?php endif; ?>
                <?php if($p['preco_frango']>0): ?>
                  <button class="btn btn-sm btn-secondary w-100 mb-1"
                          onclick="adicionarComOpcionais(<?=$p['id']?>,'<?=htmlspecialchars($p['nome'], ENT_QUOTES)?> - Carne de Frango',<?=$p['preco_frango']?>, this, false)">
                    Carne Frango - R$ <?=number_format($p['preco_frango'],2,',','.')?>
                  </button>
                <?php endif; ?>
                <?php if($p['preco_artesanal']>0): ?>
                  <button class="btn btn-sm btn-success w-100"
                          onclick="adicionarComOpcionais(<?=$p['id']?>,'<?=htmlspecialchars($p['nome'], ENT_QUOTES)?> - Carne Caseira',<?=$p['preco_artesanal']?>, this, false)">
                    Carne Caseira - R$ <?=number_format($p['preco_artesanal'],2,',','.')?>
                  </button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div id="carrinho">
  <h4>Seu Carrinho 
    <button class="btn btn-sm btn-danger float-end" onclick="toggleCarrinho()">Fechar</button>
  </h4>
  <table class="table table-sm mt-3 table-dark" id="tabelaCarrinho">
    <tr><th>Produto</th><th>Qtd</th><th>Preço</th><th></th></tr>
  </table>
  <div class="fw-bold mt-2">Total: R$ <span id="total">0,00</span></div>

  <form id="formCliente" class="mt-3" onsubmit="finalizarPedido(event)">
    <input type="text" id="nome" class="form-control mb-2" placeholder="Seu nome" required>
    <input type="tel" id="telefone" class="form-control mb-2" placeholder="Telefone (com DDD)" required>
    <input type="text" id="endereco" class="form-control mb-2" placeholder="Endereço de entrega" required>
    <textarea id="obs" class="form-control mb-2" placeholder="Observações (opcional)"></textarea>
    <select id="pagamento" class="form-select mb-2" required>
      <option value="">Forma de pagamento</option>
      <option>Dinheiro</option>
      <option>Pix</option>
      <option>Cartão</option>
    </select>
    <input type="text" id="troco" class="form-control mb-2" placeholder="Troco para quanto? (se dinheiro)">
    <?php if(!$aberto): ?>
      <p class="text-danger fw-bold">⏰ Estamos fechados! (10:00 às 22:50)</p>
    <?php endif; ?>
    <button type="submit" class="btn btn-success w-100 mt-2">✅ Finalizar Pedido</button>
  </form>
</div>

<footer>
  <?=date('Y')?> ® Mimoso Lanches – Todos os direitos reservados<br>
  Webdesigner José Luiz Balbino – Amo Mimoso do Sul
</footer>

<button id="btnCarrinhoFlutuante" onclick="toggleCarrinho()">🛒 <span id="contadorFlutuante">0</span></button>
<script>
let carrinho = [];
let opcionaisMap = <?php echo json_encode($opcionais); ?>;
const removiveis = ["Queijo","Presunto","Alface","Tomate","Ervilha","Cebola","Milho","Batata Palha","Miolo"];

// ---------- MODAL DE OPCIONAIS ----------
function adicionarComOpcionais(id,nome,preco,btn,isBebida){
  const ops = opcionaisMap[id] || [];
  let html = "<div>";
  html += "<div class='d-flex justify-content-between align-items-center mb-2'><h5 class='m-0'>Personalizar</h5><button class='btn btn-sm btn-outline-secondary' onclick='fecharModalOpc()'>Fechar</button></div>";

  if(ops.length>0){
    html += "<h6>Adicionais</h6>";
    ops.forEach(o=>{
      html+=`<div><label><input type='checkbox' data-preco='${o.preco}' value='${o.nome}'> ${o.nome} (+R$ ${parseFloat(o.preco).toFixed(2).replace('.',',')})</label></div>`;
    });
    html+="<hr>";
  }

  if(!isBebida && removiveis.length>0){
    html += "<h6>Remover</h6>";
    removiveis.forEach(r=>{
      html+=`<div><label><input type='checkbox' data-remove='1' value='${r}'> Sem ${r}</label></div>`;
    });
    html+="<hr>";
  }

  html+=`
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="fecharModalOpc()">Cancelar</button>
      <button class="btn btn-primary" onclick="confirmarOpcionais()">Adicionar</button>
    </div>
  </div>`;

  const modal=document.createElement('div');
  modal.id='modalOpc';
  modal.innerHTML=html;
  document.body.appendChild(modal);
  modal.dataset.id=id;
  modal.dataset.nome=nome;
  modal.dataset.preco=preco;
  modal.dataset.btnIndex=[...document.querySelectorAll("button")].indexOf(btn);

  modal.addEventListener('click', e=>{
    if(e.target.id==='modalOpc'){ fecharModalOpc(); }
  });
  document.addEventListener('keydown', fecharModalEscHandler);
}

function fecharModalEscHandler(e){
  if(e.key === 'Escape'){ fecharModalOpc(); }
}
function fecharModalOpc(){
  const modal=document.getElementById('modalOpc');
  if(modal) modal.remove();
  document.removeEventListener('keydown', fecharModalEscHandler);
}

function confirmarOpcionais(){
  const modal=document.getElementById('modalOpc');
  const id=parseInt(modal.dataset.id);
  let nomeBase=modal.dataset.nome;
  let preco=parseFloat(modal.dataset.preco);

  let extras=[], removidos=[];
  modal.querySelectorAll('input[type=checkbox]:not([data-remove])').forEach(ch=>{
    if(ch.checked){ extras.push(ch.value); preco+=parseFloat(ch.dataset.preco); }
  });
  modal.querySelectorAll('input[data-remove]:checked').forEach(ch=>removidos.push(ch.value));

  // nome visível inclui escolhas
  let nomeVisivel = nomeBase;
  if(extras.length>0) nomeVisivel += " acrescenta " + extras.join(", ");
  if(removidos.length>0) nomeVisivel += " sem " + removidos.join(", ");

  const existente = carrinho.find(p => p.nome === nomeVisivel);
  if(existente){ existente.qtd++; }
  else{
    carrinho.push({
      id,
      nome: nomeVisivel,
      preco,
      qtd:1,
      adicionais: extras,
      remocoes: removidos,
      observacao: ""
    });
  }
  render();
  animarProduto(document.querySelectorAll("button")[modal.dataset.btnIndex]);
  fecharModalOpc();
}

// ---------- CARRINHO ----------
function remover(nome){ carrinho = carrinho.filter(p => p.nome !== nome); render(); }

function render(){
  const tabela = document.getElementById("tabelaCarrinho");
  tabela.innerHTML = "<tr><th>Produto</th><th>Qtd</th><th>Preço</th><th></th></tr>";
  let total=0,qtdTotal=0;
  carrinho.forEach(p=>{
    const linha=tabela.insertRow();
    linha.insertCell().innerText=p.nome;
    linha.insertCell().innerText=p.qtd;
    linha.insertCell().innerText="R$ "+(p.preco*p.qtd).toFixed(2).replace(".",",");
    const tdBtn=linha.insertCell();
    tdBtn.innerHTML='<button type="button" class="btn btn-sm btn-danger">X</button>';
    tdBtn.querySelector("button").onclick=()=>remover(p.nome);
    total+=p.preco*p.qtd; qtdTotal+=p.qtd;
  });
  document.getElementById("total").innerText=total.toFixed(2).replace(".",",");
  document.getElementById("contador").innerText=qtdTotal;
  document.getElementById("contadorFlutuante").innerText=qtdTotal;
}

function toggleCarrinho(){ document.getElementById("carrinho").classList.toggle("ativo"); }

// ---------- FINALIZAR ----------
function finalizarPedido(e){
  e.preventDefault();
  if(carrinho.length===0){ alert("Carrinho vazio!"); return; }

  const nome=document.getElementById('nome').value.trim();
  const tel=document.getElementById('telefone').value.trim();
  const end=document.getElementById('endereco').value.trim();
  const obs=document.getElementById('obs').value.trim();
  const pag=document.getElementById('pagamento').value;
  const troco=document.getElementById('troco').value.trim();
  let total=0; carrinho.forEach(p=> total+=p.preco*p.qtd);

  fetch("backend/salvar_pedido.php",{
    method:"POST",headers:{"Content-Type":"application/json"},
    body:JSON.stringify({nome,telefone:tel,endereco:end,pagamento:pag,obs,troco,total,itens:carrinho})
  })
  .then(r=>r.text())
  .then(txt=>{
    const clean=txt.replace(/^\uFEFF/,'').trim();
    let resp;
    try{resp=JSON.parse(clean);}catch(e){alert("Erro ao interpretar resposta:\n"+clean.slice(0,200));return;}
    if(resp&&resp.sucesso&&resp.id){
      const dataHora = new Date().toLocaleString('pt-BR');
      let texto = `🍔 *MIMOSO LANCHES DELIVERY*\n📞 (28) 99965-2545\n🧾 *Pedido Online*\n📅 ${dataHora}\n📦 Nº do Pedido: ${resp.id}\n\n`;
      texto += `👤 Cliente: ${nome}\n📱 Telefone: ${tel}\n🏠 Endereço: ${end}\n`;
      if(obs) texto += `📝 Obs: ${obs}\n`;
      if(pag==="Dinheiro"&&troco) texto += `💵 Troco para: R$ ${troco}\n`;
      texto += `💳 Pagamento: ${pag}\n\n`;
      texto += `🍟 *Itens do Pedido:*\n`;
      carrinho.forEach(p=>{
        let linha=`• ${p.qtd}x ${p.nome}`;
        if(p.adicionais && p.adicionais.length) linha+=` acrescenta ${p.adicionais.join(", ")}`;
        if(p.remocoes && p.remocoes.length) linha+=` sem ${p.remocoes.join(", ")}`;
        linha+=` — R$ ${(p.preco*p.qtd).toFixed(2).replace(".",",")}`;
        texto+=linha+"\n";
      });
      texto += `\n💲 *Total: R$ ${total.toFixed(2).replace(".",",")}*\n`;
      texto += `\n✅ Obrigado por comprar no *Mimoso Lanches!* \n`;
      texto += `🔗 Acompanhe seu pedido: ${window.location.origin}/meu-pedido.php?id=${resp.id}`;

      alert("✅ Pedido enviado com sucesso!");
      window.location.href="https://wa.me/5528999652545?text="+encodeURIComponent(texto);
      carrinho=[]; render(); document.getElementById("carrinho").classList.remove("ativo");
    } else {
      alert("Erro ao salvar pedido: "+(resp&&resp.erro?resp.erro:"desconhecido"));
    }
  })
  .catch(err=>alert("Falha na requisição: "+err));
}

// ---------- Animação ----------
function animarProduto(btn){
  if(!btn) return;
  const img=btn.closest(".card").querySelector(".produto-img");
  if(!img) return;
  const rect=img.getBoundingClientRect();
  const clone=img.cloneNode(true);
  clone.classList.add("fly");
  clone.style.top=rect.top+"px";clone.style.left=rect.left+"px";
  clone.style.width=rect.width+"px";clone.style.height=rect.height+"px";
  document.body.appendChild(clone);
  const carrBtn=document.getElementById("btnCarrinhoFlutuante").getBoundingClientRect();
  setTimeout(()=>{
    clone.style.top=carrBtn.top+"px";
    clone.style.left=carrBtn.left+"px";
    clone.style.width="20px";clone.style.height="20px";clone.style.opacity=0.5;
  },10);
  setTimeout(()=>clone.remove(),700);
}
</script>
</body>
</html>
