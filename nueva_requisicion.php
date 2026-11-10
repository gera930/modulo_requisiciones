<?php
require_once 'header.php';
require_once 'Database.php';
require_once 'Requisicion.php';
require_once 'ClaseAlmacen.php';

$db = (new Database())->getConnection();
$req = new Requisicion($db);
$alm = new ClaseAlmacen($db);
$materiales = $alm->getAll()->fetchAll(PDO::FETCH_ASSOC);
$deptos = $db->query("SELECT * FROM departamento ORDER BY nombre_departamento")->fetchAll(PDO::FETCH_ASSOC);
$folio = $req->generarFolio();
$materialPorId = [];
foreach ($materiales as $material) { $materialPorId[(string)$material['id_material']] = $material; }

$msg = ""; $err = ""; $productosSinStock = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $detalles = [];
    $mats = $_POST['id_material'] ?? [];
    $cants = $_POST['cantidad_solicitada'] ?? [];
    $just = $_POST['justificacion'] ?? [];
    $ori = $_POST['origen_suministro'] ?? [];

    for ($i = 0; $i < count($mats); $i++) {
        if (!empty($mats[$i])) {
            $idMaterial = (string)$mats[$i];
            if (!isset($materialPorId[$idMaterial])) continue;
            $cantidad = (float)($cants[$i] ?? 0);
            $existencia = (float)$materialPorId[$idMaterial]['existencia'];
            $sinSuficienteStock = $cantidad > $existencia;
            if ($sinSuficienteStock) {
                $productosSinStock[] = [
                    'descripcion' => $materialPorId[$idMaterial]['descripcion'],
                    'cantidad' => $cantidad,
                    'existencia' => $existencia,
                    'unidad' => $materialPorId[$idMaterial]['unidad_medida']
                ];
            }
            $detalles[] = [
                'id_material' => $mats[$i],
                'numero_partida' => $i + 1,
                'cantidad_solicitada' => $cantidad,
                'justificacion' => $just[$i] ?? '',
                'origen_suministro' => $sinSuficienteStock
                    ? 'COMPRA / SERV. EXTERNO' : ($ori[$i] ?? 'ALMACEN / SERV. INTERNO'),
                'observacion' => ''
            ];
        }
    }

    if (empty($detalles)) {
        $err = "Agrega al menos un ítem.";
    } else {
        $req->folio = $_POST['folio'] ?? $folio;
        $req->fecha_solicitud = $_POST['fecha_solicitud'];
        $req->tipo_solicitud = $_POST['tipo_solicitud'];
        $req->id_depto_solicitante = $_POST['id_depto_solicitante'];
        $req->id_depto_destino = $_POST['id_depto_destino'];
        $req->id_usuario_solicita = $_SESSION['usuario_id'];

        if ($req->create($detalles)) {
            $msg = "Requisición {$req->folio} creada.";
            $folio = $req->generarFolio();
        } else {
            $err = "Error al guardar.";
            $productosSinStock = [];
        }
    }
}
?>
<h2 class="page-title">Nueva Requisición</h2>
<p class="page-subtitle">Registra los artículos que necesitas. Si un producto está agotado, la requisición se marcará para compra externa.</p>
<?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if ($msg && $productosSinStock): ?>
  <div class="alert alert-warning" role="status"><div><strong>Algunos productos no tienen existencias suficientes.</strong><ul style="margin:7px 0 0 20px">
    <?php foreach ($productosSinStock as $producto): ?>
      <li><?= htmlspecialchars($producto['descripcion']) ?>: solicitaste <?= number_format($producto['cantidad'], 2) ?> <?= htmlspecialchars($producto['unidad']) ?>; hay <?= number_format($producto['existencia'], 2) ?> disponibles. Se enviará para compra externa.</li>
    <?php endforeach; ?>
  </ul></div></div>
<?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>

<?php if (!$materiales): ?>
  <div class="empty-state"><strong>No hay materiales disponibles en el catálogo.</strong><br>Solicita al administrador que registre el material en Almacén y luego podrás agregarlo a la requisición.<br><a href="index.php" class="btn btn-outline" style="margin-top:14px">Volver al panel</a></div>
<?php else: ?>
<form method="POST">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
    <div><label>Folio</label><input type="text" name="folio" value="<?= $folio ?>" readonly></div>
    <div><label>Fecha</label><input type="date" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required></div>
    <div><label>Tipo</label><select name="tipo_solicitud" required><option>MATERIAL</option><option>SERVICIO</option></select></div>
    <div><label>Departamento Solicitante</label><select name="id_depto_solicitante" required>
      <?php foreach ($deptos as $d): ?><option value="<?= $d['id_departamento'] ?>"><?= $d['nombre_departamento'] ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Departamento Destino</label><select name="id_depto_destino" required>
      <?php foreach ($deptos as $d): ?><option value="<?= $d['id_departamento'] ?>"><?= $d['nombre_departamento'] ?></option><?php endforeach; ?>
    </select></div>
  </div>

  <table>
    <thead>
      <tr><th>Material</th><th>Cantidad</th><th>Origen</th><th>Justificación</th><th>Acción</th></tr>
    </thead>
    <tbody id="items">
      <tr>
        <td><select name="id_material[]" required onchange="actualizarOrigen(this.closest('tr'))"><option value="">Seleccionar producto</option>
          <?php foreach ($materiales as $m): ?><option value="<?= (int)$m['id_material'] ?>" data-existencia="<?= (float)$m['existencia'] ?>" data-unidad="<?= htmlspecialchars($m['unidad_medida'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($m['descripcion']) ?> (<?= htmlspecialchars($m['codigo']) ?>) — <?= (float)$m['existencia'] <= 0 ? 'AGOTADO' : number_format((float)$m['existencia'], 2).' '.htmlspecialchars($m['unidad_medida']) ?></option><?php endforeach; ?>
        </select></td>
        <td><input type="number" name="cantidad_solicitada[]" value="1" min="0.01" step="0.01" required oninput="actualizarOrigen(this.closest('tr'))"></td>
        <td><select name="origen_suministro[]"><option>ALMACEN / SERV. INTERNO</option><option>COMPRA / SERV. EXTERNO</option></select></td>
        <td><input type="text" name="justificacion[]" placeholder="Justificación"></td>
        <td><button type="button" class="btn" style="padding:4px 8px;" onclick="this.closest('tr').remove()">×</button></td>
      </tr>
    </tbody>
  </table>
  <button type="button" class="btn" style="margin-top:10px;padding:6px 12px;" onclick="addRow()">+ Agregar ítem</button>

  <div style="margin-top:15px;">
    <button type="submit" class="btn">Guardar Requisición</button>
    <a href="index.php" class="btn btn-outline" style="margin-left:10px;">Cancelar</a>
  </div>
</form>
<?php endif; ?>

<script>
function actualizarOrigen(row) {
  const select = row.querySelector('[name="id_material[]"]');
  const cantidad = row.querySelector('[name="cantidad_solicitada[]"]');
  const origen = row.querySelector('[name="origen_suministro[]"]');
  let aviso = row.querySelector('.stock-notice');
  if (!aviso) {
    aviso = document.createElement('div');
    aviso.className = 'stock-notice';
    aviso.setAttribute('role', 'status');
    aviso.setAttribute('aria-live', 'polite');
    select.closest('td').appendChild(aviso);
  }
  if (!select.value) { aviso.hidden = true; return; }
  const option = select.selectedOptions[0];
  const existencia = Number(option.dataset.existencia || 0);
  const solicitada = Number(cantidad.value || 0);
  const unidad = option.dataset.unidad || 'unidades';
  if (solicitada > existencia) {
    origen.value = 'COMPRA / SERV. EXTERNO';
    origen.dataset.auto = 'true';
    aviso.className = 'stock-notice stock-notice-warning';
    aviso.textContent = existencia <= 0
      ? 'Producto agotado: no hay existencias. Se solicitará mediante compra externa.'
      : `Existencia insuficiente: hay ${existencia} ${unidad}; solicitaste ${solicitada}. Se solicitará mediante compra externa.`;
  } else {
    if (origen.dataset.auto === 'true') {
      origen.value = 'ALMACEN / SERV. INTERNO';
      delete origen.dataset.auto;
    }
    aviso.className = 'stock-notice stock-notice-ok';
    aviso.textContent = `Existencias disponibles: ${existencia} ${unidad}.`;
  }
  aviso.hidden = false;
}
function addRow() {
  const tbody = document.getElementById('items');
  if (!tbody) return;
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select name="id_material[]" required onchange="actualizarOrigen(this.closest('tr'))"><option value="">Seleccionar producto</option><?php foreach ($materiales as $m): ?><option value="<?= (int)$m['id_material'] ?>" data-existencia="<?= (float)$m['existencia'] ?>" data-unidad="<?= htmlspecialchars($m['unidad_medida'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($m['codigo'], ENT_QUOTES, 'UTF-8') ?>) — <?= (float)$m['existencia'] <= 0 ? 'AGOTADO' : number_format((float)$m['existencia'], 2).' '.htmlspecialchars($m['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></td>
    <td><input type="number" name="cantidad_solicitada[]" value="1" min="0.01" step="0.01" required oninput="actualizarOrigen(this.closest('tr'))"></td>
    <td><select name="origen_suministro[]"><option>ALMACEN / SERV. INTERNO</option><option>COMPRA / SERV. EXTERNO</option></select></td>
    <td><input type="text" name="justificacion[]" placeholder="Justificación"></td>
    <td><button type="button" class="btn" style="padding:4px 8px;" onclick="this.closest('tr').remove()">×</button></td>`;
  tbody.appendChild(tr);
}
</script>
<?php require_once 'footer.php'; ?>
