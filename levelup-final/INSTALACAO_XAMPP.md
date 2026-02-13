# 🎮 GUIA DEFINITIVO - LEVEL UP YOUR LIFE NO XAMPP

## ✅ PASSO A PASSO (10 MINUTOS)

### 📥 PASSO 1: BAIXAR E EXTRAIR

1. Baixe o arquivo `levelup-xampp.zip` que vou criar
2. Extraia TUDO para: `C:\xampp\htdocs\`
3. Deve ficar: `C:\xampp\htdocs\levelup-xampp\`

### 🗄️ PASSO 2: CRIAR O BANCO DE DADOS

1. Abra o **XAMPP Control Panel**
2. Inicie **Apache** e **MySQL** (clique em Start)
3. Clique em **Admin** do MySQL (abre phpMyAdmin)
4. No phpMyAdmin:
   - Clique na aba **SQL** (no topo)
   - Abra o arquivo `C:\xampp\htdocs\levelup-xampp\assets\sql\schema.sql` no bloco de notas
   - Copie TODO o conteúdo
   - Cole na caixa SQL do phpMyAdmin
   - Clique em **Executar** (botão à direita embaixo)
5. Deve aparecer a mensagem: "Banco de dados levelup_life criado com sucesso"

### 🔧 PASSO 3: VERIFICAR CONFIGURAÇÃO

O arquivo `config/database.php` já está pré-configurado com:
```
Usuário: root
Senha: (vazia)
Banco: levelup_life
```

**Se sua senha do MySQL for diferente**, edite o arquivo:
`C:\xampp\htdocs\levelup-xampp\config\database.php`

### 🚀 PASSO 4: ACESSAR

Abra o navegador e acesse:
```
http://localhost/levelup-xampp/login.php
```

### ✨ PASSO 5: TESTAR

1. Clique em **"Registre-se aqui"**
2. Crie uma conta com qualquer email (ex: teste@teste.com)
3. Senha mínimo 6 caracteres
4. Você será redirecionado para criar o personagem

---

## 🎯 ESTRUTURA CORRETA

Após extrair, você deve ter:

```
C:\xampp\htdocs\levelup-xampp\
├── index.php
├── login.php
├── registro.php
├── criar-personagem.php
├── dashboard.php
├── config\
│   ├── database.php
│   └── constants.php
├── core\
│   └── Session.php
├── models\
│   ├── User.php
│   ├── Character.php
│   ├── Challenge.php
│   └── Answer.php
├── public\
│   └── css\
│       └── main.css
├── views\
│   └── dashboard\
│       └── index.php
├── api\
└── assets\
    └── sql\
        └── schema.sql
```

---

## 🐛 SOLUÇÃO DE PROBLEMAS

### Erro: "Database Connection Failed"

**Causa:** MySQL não está rodando ou banco não foi criado

**Solução:**
1. Abra XAMPP Control Panel
2. Verifique se MySQL está rodando (deve estar verde)
3. Se não estiver, clique em "Start"
4. Importe o SQL novamente (Passo 2)

### Erro: "Page not found" / Página em branco

**Causa:** Apache não está rodando

**Solução:**
1. Abra XAMPP Control Panel
2. Verifique se Apache está rodando (deve estar verde)
3. Se não estiver, clique em "Start"

### Erro: "Access denied for user 'root'"

**Causa:** Senha do MySQL diferente da configuração

**Solução:**
1. Descubra sua senha do MySQL
2. Edite `config/database.php`
3. Altere a linha: `define('DB_PASS', 'SUA_SENHA_AQUI');`

### Página de login não aparece

**Solução:**
Acesse diretamente:
```
http://localhost/levelup-xampp/login.php
```

---

## ✅ CHECKLIST DE SUCESSO

Marque conforme completar:

- [ ] XAMPP instalado
- [ ] Apache rodando (verde no XAMPP)
- [ ] MySQL rodando (verde no XAMPP)
- [ ] Pasta extraída em `C:\xampp\htdocs\levelup-xampp\`
- [ ] Banco `levelup_life` criado no phpMyAdmin
- [ ] Arquivo `config/database.php` existe
- [ ] Login acessível em `http://localhost/levelup-xampp/login.php`

---

## 🎉 PRONTO PARA USAR!

Depois que conseguir fazer login:

1. **Crie seu personagem** (nome + classe)
2. **Responda perguntas diárias** (ganhe XP!)
3. **Complete desafios**
4. **Veja sua evolução no dashboard**

---

## 📞 AINDA COM PROBLEMAS?

Me envie:
1. Print da mensagem de erro
2. Print do phpMyAdmin mostrando se o banco `levelup_life` existe
3. Print do XAMPP Control Panel mostrando Apache e MySQL

Vou te ajudar! 🚀
