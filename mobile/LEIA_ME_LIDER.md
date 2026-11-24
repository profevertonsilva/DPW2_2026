# Leia-me do líder: Mobile do AgendAki

## O que é esta parte

A pasta `mobile/` é o app do cliente do AgendAki, em Flutter, para Android. Com ele, o cliente:

- busca barbearias e vê a nota média;
- agenda (serviço, barbeiro, data e horário livre);
- acompanha, remarca e cancela os seus agendamentos;
- avalia o atendimento e recupera a senha com um código de 6 dígitos.

Como se liga às outras partes: o app busca e grava tudo na API (`backend/`), com token Bearer. O painel da barbearia (`frontend/`) usa a mesma API e mostra os agendamentos feitos pelo app. Sem a API no ar, o app abre, mas não consegue entrar nem buscar.

## Como publicar (VS Code)

1. **Tenha o repositório da turma atualizado.** Se ainda não tem: no VS Code, `Ctrl+Shift+P`, "Git: Clone", cole `https://github.com/profevertonsilva/DPW2_2026.git` e escolha onde salvar. Se já tem: abra a pasta e, em Source Control, use "Pull".
2. **Extraia o zip na raiz do clone**, a pasta `DPW2_2026` (a que tem o `README.md` e a pasta oculta `.git`). Atenção: o "Extrair tudo" do Windows sugere uma subpasta com o nome do zip no final do caminho. Apague esse final para extrair direto em `DPW2_2026`. O resultado tem que ser `DPW2_2026\mobile\`.
3. **Abra a pasta `DPW2_2026` no VS Code** e vá em Source Control (`Ctrl+Shift+G`). Devem aparecer só arquivos novos (U) dentro de `mobile/`. Nenhum arquivo modificado (M) fora dela.
4. **Faça o commit** com a mensagem `feat(mobile): app Flutter do cliente do AgendAki` e clique em "Sync Changes" (ou "Push").

### Pelo terminal

```powershell
git clone https://github.com/profevertonsilva/DPW2_2026.git   # ou, se já tiver: cd DPW2_2026; git pull
cd DPW2_2026
Expand-Archive C:\caminho\do\AgendAki_Mobile.zip -DestinationPath .
git status                    # só mobile/
git add mobile
git commit -m "feat(mobile): app Flutter do cliente do AgendAki"
git push
```

**Não commite `.env`, `build/` nem `.dart_tool/`.** O `.gitignore` da pasta já ignora esses arquivos; mesmo assim, confira o `git status` antes do commit.

## Como rodar e testar a sua parte

Pré-requisitos: Flutter estável, Android SDK com as licenças aceitas (`flutter doctor --android-licenses`) e um emulador (AVD). `flutter doctor` deve mostrar Flutter e Android toolchain sem erros.

```powershell
cd mobile
flutter pub get
flutter emulators --launch <nome-do-emulador>
flutter run
```

**Endereço da API:** o padrão é `http://10.0.2.2:8000/api`, que é o computador visto pelo emulador. Para usar outro, há duas formas:

- `flutter run --dart-define=API_URL=http://<IP-do-computador>:8000/api`
- copie `.env.example` para `.env`, ajuste o `API_URL` e rode `flutter run --dart-define-from-file=.env`

Contas de teste no `README_AGENDAKI.md` da raiz (por exemplo, `cliente@agendaki.test`).

**Verificações:**

```powershell
flutter analyze
flutter test
flutter build apk --debug
```

## Do que esta parte depende

- **A API (`backend/`) no ar**, iniciada com `php artisan serve --host=0.0.0.0 --port=8000`. Sem o `--host=0.0.0.0`, o emulador não alcança a API. A API, por sua vez, precisa do PostgreSQL.
- **Ordem:** publique depois do Back-end. Os três commits são independentes, mas o app só funciona com a API.
