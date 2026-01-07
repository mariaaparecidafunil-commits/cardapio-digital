<?php
// admin/index_fallback.php  — temporário. Mostra links administrativos básicos.
// NÃO substitua o index.php original sem backup. Use só para entrar e checar.

echo "<!doctype html><meta charset='utf-8'><title>Admin fallback</title>";
echo "<h2>Admin fallback — Acesso temporário</h2>";
echo "<p>Se este arquivo abrir, o PHP está funcionando. Verifique os links abaixo:</p>";
echo "<ul>";
echo "<li><a href='/admin/login.php'>Página de login (normal)</a></li>";
echo "<li><a href='/admin/produtos.php'>Produtos (se existir)</a></li>";
echo "<li><a href='/admin/pedidos.php'>Pedidos (se existir)</a></li>";
echo "<li><a href='/admin/index.php'>Tentar index.php real</a></li>";
echo "</ul>";
echo "<p><strong>IMPORTANTE:</strong> delete este arquivo depois.</p>";
