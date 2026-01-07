# Cardápio Digital – Mimoso Lanches 🍔

Sistema de **Cardápio Digital com painel administrativo**, desenvolvido em **PHP + MySQL** e utilizado em produção em uma lanchonete real.

Este repositório foi organizado como portfólio de desenvolvimento back-end, mostrando um sistema completo com fluxo real de uso.

---

## ✨ Funcionalidades principais

- Catálogo de produtos (lanches, bebidas, etc.)
- Cadastro de **categorias**, **opcionais** e **variações de preço**
- Cadastro e controle de **insumos** (composição dos produtos)
- Cálculo de custo e margem com base nos insumos
- Tela de **pedidos em tempo real** para a lanchonete
- Mudança de status do pedido (novo, em preparo, pronto, entregue)
- Impressão de pedidos (modelo térmica / A4)
- Relatórios básicos e módulo financeiro
- Painel administrativo protegido por login

---

## 🛠 Tecnologias utilizadas

- **PHP** (mysqli)
- **MySQL**
- **HTML5 / CSS3 / JavaScript**
- **Bootstrap 5**
- Servidor **Apache** (uso de `.htaccess`)

---

## 📁 Estrutura geral (resumo)

- `/` – parte pública (cardápio, pedido, login do cliente)
- `/admin` – painel administrativo (produtos, pedidos, relatórios, insumos, etc.)
- `/backend` – conexão com o banco e rotinas de backend
- `.gitignore` – regras para não versionar arquivos sensíveis
- `backend/config.example.php` – modelo de configuração do banco

---

## 🚀 Como rodar o projeto localmente

1. **Clonar o repositório**

   ```bash
   git clone https://github.com/mariaaparecidafunil-commits/cardapio-digital.git
