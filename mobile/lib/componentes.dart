import 'package:flutter/material.dart';

// Tokens do padrão visual do front-end da turma (adrianefranco/agendaki-frontend,
// src/index.css). Os nomes em comentário são as variáveis CSS de lá.
const Color corPrimaria = Color(0xFF1F3A8A); // --pri
const Color corPrimariaForte = Color(0xFF152863); // --pri-f
const Color corPrimariaSuave = Color(0xFFE8EDFB); // --pri-s
const Color corDestaque = Color(0xFFF0662B); // --dest
const Color corFundo = Color(0xFFF7F8FA); // --bg
const Color corSuperficie = Colors.white; // --sup
const Color corSutil = Color(0xFFEFF1F5); // --sut
const Color corBorda = Color(0xFFD9DEE7); // --bd
const Color corBordaForte = Color(0xFFAFB7C4); // --bd-f
const Color corTexto = Color(0xFF131722); // --tx
const Color corTextoSuave = Color(0xFF5B6472); // --tx2
const Color corTextoFraco = Color(0xFF8B94A3); // --tx3
const Color corSucesso = Color(0xFF0E7A46); // --suc
const Color corSucessoSuave = Color(0xFFE2F5EB); // --suc-s
const Color corAlerta = Color(0xFFB87503); // --ale
const Color corAlertaSuave = Color(0xFFFDF3DE); // --ale-s
const Color corErro = Color(0xFFC0261B); // --err
const Color corErroSuave = Color(0xFFFDEAE8); // --err-s

// Raios: --r1 8, --r2 12, --r3 20 e pílula.
const double raio1 = 8;
const double raio2 = 12;
const double raio3 = 20;

/// Rótulo de campo (.campo-rot): 13 px, peso 500, cor secundária.
const TextStyle estiloRotulo = TextStyle(
  fontSize: 13,
  fontWeight: FontWeight.w500,
  color: corTextoSuave,
);

/// Sombra dos cartões de estabelecimento (.card-estab).
const List<BoxShadow> sombraCartao = [
  BoxShadow(color: Color(0x0D121722), blurRadius: 8, offset: Offset(0, 2)),
];

ThemeData temaAgendaki() {
  final esquema = ColorScheme.fromSeed(
    seedColor: corPrimaria,
    primary: corPrimaria,
    onPrimary: Colors.white,
    secondary: corDestaque,
    surface: corSuperficie,
    onSurface: corTexto,
    error: corErro,
    outline: corBorda,
  );
  final bordaCampo = OutlineInputBorder(
    borderRadius: BorderRadius.circular(raio1),
    borderSide: const BorderSide(color: corBorda),
  );
  return ThemeData(
    useMaterial3: true,
    colorScheme: esquema,
    scaffoldBackgroundColor: corFundo,
    dividerColor: corBorda,
    // Barra de título (.barra): fundo branco, título de 17 px e borda inferior.
    appBarTheme: const AppBarTheme(
      backgroundColor: corSuperficie,
      foregroundColor: corTexto,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      shape: Border(bottom: BorderSide(color: corBorda)),
      titleTextStyle: TextStyle(
        fontSize: 17,
        fontWeight: FontWeight.w600,
        color: corTexto,
      ),
    ),
    // Caixa de campo (.campo-box): 46 px, borda de 1 px e foco de 2 px na cor primária.
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: corSuperficie,
      isDense: false,
      hintStyle: const TextStyle(color: corTextoFraco, fontSize: 15),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
      border: bordaCampo,
      enabledBorder: bordaCampo,
      focusedBorder: bordaCampo.copyWith(
        borderSide: const BorderSide(color: corPrimaria, width: 2),
      ),
      errorBorder: bordaCampo.copyWith(
        borderSide: const BorderSide(color: corErro),
      ),
      focusedErrorBorder: bordaCampo.copyWith(
        borderSide: const BorderSide(color: corErro, width: 2),
      ),
      errorStyle: const TextStyle(color: corErro, fontSize: 11),
    ),
    // Botões (.btn): raio de 12 px, 15 px, peso 500.
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: corPrimaria,
        foregroundColor: Colors.white,
        disabledBackgroundColor: corPrimaria.withValues(alpha: 0.5),
        disabledForegroundColor: Colors.white,
        minimumSize: const Size.fromHeight(50),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(raio2),
        ),
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: corPrimaria,
        backgroundColor: corSuperficie,
        side: const BorderSide(color: corBorda),
        minimumSize: const Size.fromHeight(50),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(raio2),
        ),
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: corPrimaria,
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: corSuperficie,
      indicatorColor: corPrimariaSuave,
      surfaceTintColor: Colors.transparent,
      iconTheme: WidgetStateProperty.resolveWith(
        (estados) => IconThemeData(
          color: estados.contains(WidgetState.selected)
              ? corPrimaria
              : corTextoSuave,
        ),
      ),
      labelTextStyle: WidgetStateProperty.resolveWith(
        (estados) => TextStyle(
          fontSize: 12,
          fontWeight: estados.contains(WidgetState.selected)
              ? FontWeight.w600
              : FontWeight.w500,
          color: estados.contains(WidgetState.selected)
              ? corPrimaria
              : corTextoSuave,
        ),
      ),
    ),
    tabBarTheme: const TabBarThemeData(
      labelColor: corPrimaria,
      unselectedLabelColor: corTextoSuave,
      indicatorColor: corPrimaria,
      dividerColor: corBorda,
      labelStyle: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: corSuperficie,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(raio3)),
      titleTextStyle: const TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: corTexto,
      ),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: corTexto,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(raio1)),
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(
      color: corPrimaria,
    ),
  );
}

/// Botão principal fixo na base da tela.
class BarraInferior extends StatelessWidget {
  const BarraInferior({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
        decoration: const BoxDecoration(
          color: corSuperficie,
          border: Border(top: BorderSide(color: corBorda)),
        ),
        child: child,
      ),
    );
  }
}

/// Botão com indicador de carregamento (.btn-primario e .btn-destaque).
class BotaoPrincipal extends StatelessWidget {
  const BotaoPrincipal({
    super.key,
    required this.texto,
    required this.aoTocar,
    this.carregando = false,
    this.destaque = false,
  });

  final String texto;
  final VoidCallback? aoTocar;
  final bool carregando;
  final bool destaque;

  @override
  Widget build(BuildContext context) {
    return FilledButton(
      style: destaque
          ? FilledButton.styleFrom(
              backgroundColor: corDestaque,
              disabledBackgroundColor: corDestaque.withValues(alpha: 0.5),
            )
          : null,
      onPressed: carregando ? null : aoTocar,
      child: carregando
          ? const SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                color: Colors.white,
              ),
            )
          : Text(texto),
    );
  }
}

/// Cartão branco com borda (.bloco / .card-ag), opcionalmente selecionável.
class Cartao extends StatelessWidget {
  const Cartao({
    super.key,
    required this.child,
    this.aoTocar,
    this.selecionado = false,
    this.sombra = false,
    this.padding = const EdgeInsets.all(16),
  });

  final Widget child;
  final VoidCallback? aoTocar;
  final bool selecionado;
  final bool sombra;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(raio2),
        boxShadow: sombra ? sombraCartao : null,
      ),
      child: Material(
        color: selecionado ? corPrimariaSuave : corSuperficie,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(raio2),
          side: BorderSide(
            color: selecionado ? corPrimaria : corBorda,
            width: selecionado ? 2 : 1,
          ),
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(raio2),
          onTap: aoTocar,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}

/// Capa da barbearia (.estabelecimento-capa): degradê da cor primária para a de destaque.
class CapaBarbearia extends StatelessWidget {
  const CapaBarbearia({super.key, this.altura = 140});

  final double altura;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: altura,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(raio3),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xE61F3A8A), Color(0xCCF0662B)],
        ),
      ),
      child: const Icon(Icons.content_cut, size: 44, color: Colors.white),
    );
  }
}

/// Miniatura da barbearia nos resultados (.card-estab .foto): 72 px, raio de 10 px.
class FotoBarbearia extends StatelessWidget {
  const FotoBarbearia({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 64,
      height: 64,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xE61F3A8A), Color(0xCCF0662B)],
        ),
      ),
      child: const Icon(Icons.content_cut, color: Colors.white),
    );
  }
}

/// Barra de progresso das etapas do agendamento.
class Etapas extends StatelessWidget {
  const Etapas({super.key, required this.atual, this.total = 3});

  final int atual;
  final int total;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Row(
        children: [
          for (var i = 1; i <= total; i++)
            Expanded(
              child: Container(
                height: 4,
                margin: EdgeInsets.only(right: i < total ? 6 : 0),
                decoration: BoxDecoration(
                  color: i <= atual ? corPrimaria : corBorda,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Etiqueta de status (.tag): pílula de 11 px, nas variantes marca, sucesso e erro.
class EtiquetaStatus extends StatelessWidget {
  const EtiquetaStatus(this.status, {super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (texto, fundo, frente) = switch (status) {
      'agendado' => ('Agendado', corPrimariaSuave, corPrimaria),
      'realizado' => ('Realizado', corSucessoSuave, corSucesso),
      'cancelado' => ('Cancelado', corErroSuave, corErro),
      _ => (status, corSutil, corTextoSuave),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: fundo,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        texto,
        style: TextStyle(
          color: frente,
          fontSize: 11,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.2,
        ),
      ),
    );
  }
}

/// Caixa de mensagem (.checkin-erro, .checkin-sucesso): fundo suave e borda na cor do estado.
class CaixaMensagem extends StatelessWidget {
  const CaixaMensagem({
    super.key,
    required this.texto,
    this.titulo,
    this.tipo = TipoMensagem.erro,
  });

  final String texto;
  final String? titulo;
  final TipoMensagem tipo;

  @override
  Widget build(BuildContext context) {
    final (fundo, frente) = switch (tipo) {
      TipoMensagem.erro => (corErroSuave, corErro),
      TipoMensagem.sucesso => (corSucessoSuave, corSucesso),
      TipoMensagem.alerta => (corAlertaSuave, corAlerta),
    };
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: fundo,
        borderRadius: BorderRadius.circular(raio1),
        border: Border.all(color: frente.withValues(alpha: 0.6)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (titulo != null) ...[
            Text(
              titulo!,
              style: TextStyle(
                color: frente,
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
          ],
          Text(texto, style: TextStyle(color: frente, fontSize: 14)),
        ],
      ),
    );
  }
}

enum TipoMensagem { erro, sucesso, alerta }

/// Estado de erro com botão de tentar de novo.
class MensagemErro extends StatelessWidget {
  const MensagemErro({super.key, required this.mensagem, this.aoTentar});

  final String mensagem;
  final VoidCallback? aoTentar;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: corErroSuave,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.wifi_off, size: 30, color: corErro),
            ),
            const SizedBox(height: 14),
            Text(
              mensagem,
              textAlign: TextAlign.center,
              style: const TextStyle(color: corTexto, fontSize: 15),
            ),
            if (aoTentar != null) ...[
              const SizedBox(height: 16),
              SizedBox(
                width: 200,
                child: OutlinedButton(
                  onPressed: aoTentar,
                  child: const Text('Tentar de novo'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Estado vazio (.checkin-vazio): caixa tracejada com ícone e texto centralizado.
class Vazio extends StatelessWidget {
  const Vazio(this.mensagem, {super.key, this.icone = Icons.event_busy});

  final String mensagem;
  final IconData icone;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: corPrimariaSuave,
                shape: BoxShape.circle,
              ),
              child: Icon(icone, size: 30, color: corPrimaria),
            ),
            const SizedBox(height: 14),
            Text(
              mensagem,
              textAlign: TextAlign.center,
              style: const TextStyle(color: corTextoSuave, fontSize: 15),
            ),
          ],
        ),
      ),
    );
  }
}

/// Linha "rótulo .... valor" do resumo.
class LinhaResumo extends StatelessWidget {
  const LinhaResumo(this.rotulo, this.valor, {super.key});

  final String rotulo;
  final String valor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            rotulo,
            style: const TextStyle(color: corTextoSuave, fontSize: 14),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Text(
              valor,
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: corTexto,
                fontSize: 14,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

void avisar(BuildContext context, String mensagem) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(mensagem)));
}
