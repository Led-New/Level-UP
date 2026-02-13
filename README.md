# 🎮 Level Up Your Life - Sistema RPG de Gamificação

**Transforme sua vida em uma jornada épica!**

Sistema completo de gamificação onde você cria um personagem, evolui atributos com ações da vida real, completa desafios diários e acompanha seu progresso.

---

## 🚀 INSTALAÇÃO RÁPIDA (5 MINUTOS)

### 1️⃣ Extrair
Extraia para: `C:\xampp\htdocs\`
Vai criar: `C:\xampp\htdocs\levelup-final\`

### 2️⃣ Criar Banco
1. Inicie Apache e MySQL no XAMPP
2. Acesse: http://localhost/phpmyadmin
3. Clique em "SQL"
4. Abra: `assets/sql/schema.sql` no bloco de notas
5. Copie TODO o conteúdo e cole no phpMyAdmin
6. Clique em "Executar"

### 3️⃣ Acessar
```
http://localhost/levelup-final/
```

---

## 📁 ARQUIVOS PRINCIPAIS

- **login.php** - Login
- **registro.php** - Criar conta
- **criar-personagem.php** - Criar personagem com 4 classes
- **dashboard.php** - Dashboard com gráficos
- **perguntas-diarias.php** - Responder perguntas e ganhar XP
- **logout.php** - Sair

---

## 🎯 COMO USAR

1. **Registre-se** (qualquer email + senha mínimo 6 caracteres)
2. **Crie seu personagem** (escolha nome e classe)
3. **Veja o dashboard** (seus atributos e progresso)
4. **Responda perguntas diárias** (ganhe XP e evolua!)
5. **Acompanhe sua evolução**

---

## ⚔️ CLASSES DISPONÍVEIS

- **Guerreiro** 💪 - Foco em força física (+2 Força)
- **Mago** 🧠 - Especialista em conhecimento (+2 Inteligência)
- **Assassino** 🎯 - Mestre em disciplina (+2 Disciplina)
- **Estrategista** ⚖️ - Equilíbrio perfeito (+1 em todos)

---

## 📊 SISTEMA DE ATRIBUTOS

- **Força** 💪 - Exercícios físicos
- **Inteligência** 🧠 - Estudos
- **Disciplina** 🎯 - Cumprimento de objetivos
- **Energia** ⚡ - Qualidade do sono
- **Espírito** ✨ - Meditação e bem-estar

---

## 🏆 SISTEMA DE PROGRESSÃO

### XP e Níveis
- Cada ação gera XP
- Level up automático
- Fórmula: XP necessário = 100 + (nível * 50)

### Ranks
- **Rank E**: Níveis 1-4
- **Rank D**: Níveis 5-9
- **Rank C**: Níveis 10-14
- **Rank B**: Níveis 15-19
- **Rank A**: Níveis 20-29
- **Rank S**: Níveis 30+

---

## 🐛 PROBLEMAS?

### Erro de conexão com banco
- Verifique se MySQL está rodando
- Confirme que o banco `levelup_life` foi criado
- Senha padrão do XAMPP é vazia

### Página em branco
- Verifique se Apache está rodando
- Veja erros em: `C:\xampp\apache\logs\error.log`

---

## 🎉 PRONTO!

Agora é só jogar e evoluir! 🚀

**Level Up!** ⚔️
