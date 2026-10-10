<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirectWith(string $message, string $type = 'success'): never {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        redirectWith('La sesión expiró. Intenta nuevamente.', 'error');
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM envios WHERE id = ?');
            $stmt->execute([$id]);
            redirectWith('El envío fue eliminado.');
        }
        redirectWith('No se pudo identificar el envío.', 'error');
    }

    if (in_array($action, ['create', 'update'], true)) {
        $destinatario = trim((string)($_POST['destinatario'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $estado = (string)($_POST['estado'] ?? 'Pendiente');
        $validStates = ['Pendiente', 'En camino', 'Entregado'];
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if ($destinatario === '' || $direccion === '' || $descripcion === '') {
            redirectWith('Completa destinatario, dirección y descripción.', 'error');
        }
        if (mb_strlen($destinatario) > 150 || mb_strlen($direccion) > 255 || !in_array($estado, $validStates, true)) {
            redirectWith('Revisa los datos ingresados.', 'error');
        }
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO envios (destinatario, direccion, descripcion, estado) VALUES (?, ?, ?, ?)');
            $stmt->execute([$destinatario, $direccion, $descripcion, $estado]);
            redirectWith('¡Envío creado! Ya está en tu lista.');
        } elseif ($id) {
            $stmt = $pdo->prepare('UPDATE envios SET destinatario = ?, direccion = ?, descripcion = ?, estado = ? WHERE id = ?');
            $stmt->execute([$destinatario, $direccion, $descripcion, $estado, $id]);
            redirectWith('Los cambios del envío se guardaron.');
        }
        redirectWith('No se pudo guardar el envío.', 'error');
    }
}

$edit = null;
if (isset($_GET['editar']) && ctype_digit((string)$_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM envios WHERE id = ?');
    $stmt->execute([(int)$_GET['editar']]);
    $edit = $stmt->fetch() ?: null;
}
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['estado'] ?? '');
$sql = 'SELECT * FROM envios WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (destinatario LIKE ? OR direccion LIKE ? OR descripcion LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if (in_array($statusFilter, ['Pendiente', 'En camino', 'Entregado'], true)) {
    $sql .= ' AND estado = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY creado_en DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$envios = $stmt->fetchAll();

$stats = $pdo->query("SELECT COUNT(*) total, SUM(estado='Pendiente') pendientes, SUM(estado='En camino') camino, SUM(estado='Entregado') entregados FROM envios")->fetch();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$form = $edit ?: ['id' => '', 'destinatario' => '', 'direccion' => '', 'descripcion' => '', 'estado' => 'Pendiente'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ruta bonita · Gestión de envíos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-icon">↗</span><span>ruta<span class="brand-light">bonita</span><small>GESTIÓN DE ENVÍOS</small></span></a>
    <div class="side-label">ESPACIO DE TRABAJO</div>
    <a class="nav-item active" href="index.php"><span>▦</span> Todos los envíos</a>
    <div class="sidebar-note"><div class="note-orbit">✦</div><strong>Un envío a la vez.</strong><p>Organiza tus entregas con claridad y un toque de color.</p><div class="note-line"><span></span><span></span><span></span></div></div>
    <div class="sidebar-bottom"><div class="avatar">RB</div><div><strong>Ruta Bonita</strong><small>Panel de operaciones</small></div><span class="online-dot"></span></div>
  </aside>
  <main class="main-content">
    <header class="topbar"><div><span class="eyebrow">TU CENTRO DE CONTROL</span><h1>Hola, qué bueno verte <span>✳</span></h1><p>Todo listo para que cada paquete llegue a su destino.</p></div><div class="date-pill"><span class="date-icon">◷</span><?= h(date('d M, Y')) ?></div></header>
    <?php if ($flash): ?><div class="flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?><button type="button" onclick="this.parentElement.remove()" aria-label="Cerrar">×</button></div><?php endif; ?>
    <section class="stats-grid">
      <article class="stat-card stat-total"><div class="stat-top"><span class="stat-icon">▣</span><span class="stat-tag">EN TOTAL</span></div><div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div><div class="stat-caption">Envíos registrados</div><div class="stat-decoration deco-total">↗</div></article>
      <article class="stat-card"><div class="stat-top"><span class="stat-icon icon-orange">◷</span><span class="stat-tag">POR PREPARAR</span></div><div class="stat-number"><?= (int)($stats['pendientes'] ?? 0) ?></div><div class="stat-caption">Pendientes</div><div class="stat-decoration deco-orange">✳</div></article>
      <article class="stat-card"><div class="stat-top"><span class="stat-icon icon-blue">➜</span><span class="stat-tag">EN MOVIMIENTO</span></div><div class="stat-number"><?= (int)($stats['camino'] ?? 0) ?></div><div class="stat-caption">En camino</div><div class="stat-decoration deco-blue">⌁</div></article>
      <article class="stat-card"><div class="stat-top"><span class="stat-icon icon-green">✓</span><span class="stat-tag">COMPLETADOS</span></div><div class="stat-number"><?= (int)($stats['entregados'] ?? 0) ?></div><div class="stat-caption">Entregados</div><div class="stat-decoration deco-green">✦</div></article>
    </section>

    <section class="workspace">
      <div class="list-panel">
        <div class="panel-heading"><div><span class="eyebrow">RESUMEN DE ACTIVIDAD</span><h2>Tus envíos <span class="count-bubble"><?= count($envios) ?></span></h2><p>Consulta, busca y mantén cada entrega al día.</p></div><button class="primary-btn" type="button" onclick="openForm()"><span>＋</span> Nuevo envío</button></div>
        <form class="filters" method="get"><label class="search-box"><span>⌕</span><input type="search" name="q" value="<?= h($search) ?>" placeholder="Buscar destinatario, dirección..."></label><select name="estado" aria-label="Filtrar por estado"><option value="">Todos los estados</option><?php foreach (['Pendiente','En camino','Entregado'] as $s): ?><option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select><button class="filter-btn" type="submit">Filtrar</button><?php if ($search !== '' || $statusFilter !== ''): ?><a class="clear-filter" href="index.php">Limpiar</a><?php endif; ?></form>

        <?php if (!$envios): ?>
          <div class="empty-state"><div class="empty-art"><span>✉</span><i>✦</i></div><h3><?= ($search !== '' || $statusFilter !== '') ? 'No encontramos resultados' : 'Tu ruta empieza aquí' ?></h3><p><?= ($search !== '' || $statusFilter !== '') ? 'Prueba con otra búsqueda o cambia el filtro.' : 'Crea tu primer envío y empieza a organizar tus entregas.' ?></p><?php if ($search === '' && $statusFilter === ''): ?><button class="primary-btn" type="button" onclick="openForm()">＋ Crear primer envío</button><?php endif; ?></div>
        <?php else: ?>
          <div class="shipment-list">
          <?php foreach ($envios as $e): $badge = $e['estado'] === 'Entregado' ? 'delivered' : ($e['estado'] === 'En camino' ? 'moving' : 'pending'); ?>
            <article class="shipment-card">
              <div class="package-icon <?= h($badge) ?>"><span>▧</span></div>
              <div class="shipment-info"><div class="recipient-row"><h3><?= h($e['destinatario']) ?></h3><span class="status <?= h($badge) ?>"><i></i><?= h($e['estado']) ?></span></div><p class="address"><span>⌖</span><?= h($e['direccion']) ?></p><p class="description"><?= nl2br(h($e['descripcion'])) ?></p><div class="shipment-meta"><span>ENVÍO #<?= (int)$e['id'] ?></span><span>·</span><span><?= h(date('d/m/Y · H:i', strtotime($e['creado_en']))) ?></span></div></div>
              <div class="card-actions"><a class="action-btn edit-btn" href="index.php?editar=<?= (int)$e['id'] ?>" title="Editar envío" aria-label="Editar envío">✎</a><form method="post" onsubmit="return confirm('¿Seguro que deseas eliminar este envío?');"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$e['id'] ?>"><button class="action-btn delete-btn" type="submit" title="Eliminar envío" aria-label="Eliminar envío">⌫</button></form></div>
            </article>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="list-footer"><span>Mostrando <strong><?= count($envios) ?></strong> <?= count($envios) === 1 ? 'envío' : 'envíos' ?></span><span class="footer-sparkle">Hecho para llegar lejos <b>✦</b></span></div>
      </div>
      <aside class="tips-panel"><div class="tips-illustration"><div class="sun"></div><div class="cloud cloud-one"></div><div class="cloud cloud-two"></div><div class="route-path"></div><div class="parcel">▧</div><div class="pin">●</div></div><span class="eyebrow">UN CONSEJO PARA TI</span><h3>Cada entrega cuenta.</h3><p>Mantén actualizado el estado de cada envío para saber qué está pendiente, qué va en camino y qué ya llegó.</p><div class="tip-divider"></div><div class="tip-check"><span>✓</span><div><strong>Datos claros</strong><small>Verifica dirección y destinatario.</small></div></div><div class="tip-check"><span>✓</span><div><strong>Estados al día</strong><small>Actualiza cada cambio de ruta.</small></div></div><div class="tips-bottom">PEQUEÑOS PASOS, GRANDES DESTINOS <span>↗</span></div></aside>
    </section>
    <footer class="page-footer"><span>© <?= date('Y') ?> Ruta Bonita</span><span>Organiza. Envía. Sonríe. <b>♥</b></span></footer>
  </main>
</div>

<div class="modal-backdrop <?= $edit ? 'visible' : '' ?>" id="shipmentModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <div class="modal-header"><div class="modal-symbol">✉</div><button class="modal-close" type="button" onclick="closeForm()" aria-label="Cerrar">×</button><span class="eyebrow"><?= $edit ? 'ACTUALIZA LA INFORMACIÓN' : 'NUEVA ENTREGA' ?></span><h2 id="modalTitle"><?= $edit ? 'Editar envío' : 'Vamos a crear un envío' ?></h2><p>Completa los detalles para que todo llegue a buen puerto.</p></div>
    <form method="post" class="shipment-form"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= h($form['id']) ?>">
      <label>Nombre del destinatario <span>*</span><input name="destinatario" maxlength="150" required value="<?= h($form['destinatario']) ?>" placeholder="Ej. Laura Martínez"></label>
      <label>Dirección de entrega <span>*</span><input name="direccion" maxlength="255" required value="<?= h($form['direccion']) ?>" placeholder="Calle, número, barrio, ciudad"></label>
      <label>Descripción del envío <span>*</span><textarea name="descripcion" required rows="3" placeholder="¿Qué contiene el envío?"><?= h($form['descripcion']) ?></textarea></label>
      <label>Estado del envío<select name="estado"><?php foreach (['Pendiente','En camino','Entregado'] as $s): ?><option value="<?= h($s) ?>" <?= $form['estado'] === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
      <div class="form-actions"><button class="secondary-btn" type="button" onclick="closeForm()">Cancelar</button><button class="primary-btn" type="submit"><?= $edit ? 'Guardar cambios' : 'Crear envío' ?> <span>↗</span></button></div>
    </form>
  </div>
</div>
<script>
const modal = document.getElementById('shipmentModal');
function openForm(){ modal.classList.add('visible'); document.body.classList.add('modal-open'); const first=modal.querySelector('input[name="destinatario"]'); if(first) first.focus(); }
function closeForm(){ modal.classList.remove('visible'); document.body.classList.remove('modal-open'); if(new URLSearchParams(location.search).has('editar')) location.href='index.php'; }
modal.addEventListener('click', e => { if(e.target===modal) closeForm(); });
document.addEventListener('keydown', e => { if(e.key==='Escape' && modal.classList.contains('visible')) closeForm(); });
if(modal.classList.contains('visible')) document.body.classList.add('modal-open');
</script>
</body>
</html>
