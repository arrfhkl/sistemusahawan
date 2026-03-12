<?php
/* ============================================================
   chat_room.php
   Sistem Usahawan Pahang — Chat Room dengan File Upload
============================================================ */
if (session_status() === PHP_SESSION_NONE) session_start();
include "connection.php";

if (!isset($_SESSION['usahawan_id'])) {
    header("Location: login.php"); exit;
}

$user_id    = (int)$_SESSION['usahawan_id'];
$chat_id    = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
$partner_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$ref_type   = isset($_GET['ref_type']) ? trim($_GET['ref_type']) : '';
$ref_id     = isset($_GET['ref_id'])   ? (int)$_GET['ref_id']   : 0;

$no_chat = ($chat_id === 0 && $partner_id === 0);

if (!$no_chat) {

    /* Update online status */
    $conn->query("
        INSERT INTO user_online_status (user_id, last_active) VALUES ($user_id, NOW())
        ON DUPLICATE KEY UPDATE last_active = NOW()
    ");

    /* Resolve chat_id / partner_id */
    if ($chat_id > 0) {
        $s = $conn->prepare("SELECT user_low, user_high FROM chat_rooms WHERE id=? AND (user_low=? OR user_high=?) LIMIT 1");
        $s->bind_param("iii", $chat_id, $user_id, $user_id);
        $s->execute();
        $chat = $s->get_result()->fetch_assoc();
        if (!$chat) die("Chat tidak dijumpai.");
        $partner_id = ($user_id == $chat['user_low']) ? $chat['user_high'] : $chat['user_low'];
    } else {
        $low  = min($user_id, $partner_id);
        $high = max($user_id, $partner_id);
        $s = $conn->prepare("SELECT id FROM chat_rooms WHERE user_low=? AND user_high=? LIMIT 1");
        $s->bind_param("ii", $low, $high);
        $s->execute();
        $ex = $s->get_result()->fetch_assoc();
        if ($ex) {
            $url = "chat_room.php?chat_id=" . $ex['id'];
            if (!empty($ref_type) && $ref_id > 0) $url .= "&ref_type=$ref_type&ref_id=$ref_id";
            header("Location: $url"); exit;
        }
    }

    /* Partner info */
    $s2 = $conn->prepare("SELECT nama, avatar FROM usahawan WHERE id=? LIMIT 1");
    $s2->bind_param("i", $partner_id);
    $s2->execute();
    $partner = $s2->get_result()->fetch_assoc();
    if (!$partner) die("Pengguna tidak dijumpai.");

    $partner_name   = $partner['nama'];
    $partner_avatar = !empty($partner['avatar']) ? $partner['avatar'] : 'assets/img/default_avatar.jpg';

    /* Mark as read */
    if ($chat_id > 0) {
        $conn->query("UPDATE chat_messages SET is_read=1 WHERE chat_id=$chat_id AND sender_id!=$user_id AND is_read=0");
    }

    /* Context card */
    $ctx_card = null;
    if (!empty($_GET['card_type'])) {
        $ctx_card = [
            'type'       => $_GET['card_type'],
            'nama'       => $_GET['card_nama']       ?? '',
            'lokasi'     => $_GET['card_lokasi']     ?? '',
            'gambar'     => $_GET['card_gambar']     ?? '',
            'perniagaan' => $_GET['card_perniagaan'] ?? '',
            'url'        => $_GET['card_url']        ?? '',
        ];
        if ($ctx_card['type'] === 'produk') $ctx_card['harga'] = $_GET['card_harga'] ?? 0;
    } elseif ($chat_id > 0) {
        $cs = $conn->prepare("SELECT message FROM chat_messages WHERE chat_id=? AND message_type='card' ORDER BY id ASC LIMIT 1");
        $cs->bind_param("i", $chat_id);
        $cs->execute();
        $cr = $cs->get_result()->fetch_assoc();
        if ($cr) $ctx_card = json_decode($cr['message'], true);
    }
}

$produk_chat = null;

// Fallback: if produk_id not in URL, get it from chat_rooms
if ($produk_id === 0 && isset($chat['produk_id']) && $chat['produk_id'] > 0) {
    $produk_id = (int)$chat['produk_id'];
}

if ($produk_id > 0) {
    $stmtProduk = $conn->prepare("
        SELECT id, nama, harga, gambar_url, stok
        FROM produk
        WHERE id = ?
        LIMIT 1
    ");
    $stmtProduk->bind_param("i", $produk_id);
    $stmtProduk->execute();
    $produk_chat = $stmtProduk->get_result()->fetch_assoc();
}

include "header.php";
?>
<!DOCTYPE html>

<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat — Sistem Usahawan Pahang</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ============================================================
   VARIABLES & RESET
============================================================ */
:root {
  --navy:        #001f3f;
  --blue:        #003399;
  --gold:        #c9a14a;
  --gold-light:  #f7e7c6;
  --gold-dark:   #b89544;
  --gold-faint:  rgba(201,161,74,0.12);
  --border:      rgba(0,0,0,0.08);
  --text:        #0f172a;
  --text-mid:    #334155;
  --text-muted:  #64748b;
  --white:       #ffffff;
  --bg-chat:     #f5f0e8;
  --bg-bubble-me: linear-gradient(135deg,#d4b26a 0%,#b89544 100%);
  --bubble-other: #ffffff;
  --radius-lg:   20px;
  --radius-md:   14px;
  --radius-sm:   8px;
  --transition:  0.2s cubic-bezier(.4,0,.2,1);
  --shadow-sm:   0 1px 4px rgba(0,0,0,0.07);
  --shadow-md:   0 4px 16px rgba(0,0,0,0.10);
  --shadow-lg:   0 12px 40px rgba(0,0,0,0.15);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg-chat); }

/* ============================================================
   LAYOUT
============================================================ */
.chat-wrap {
  position: fixed;
  top: 90px; left: 0; right: 0; bottom: 0;
  display: flex;
  padding: 16px 20px 20px;
  gap: 14px;
  background:
    url('assets/img/jatapahang.png') repeat,
    linear-gradient(160deg,#f8f4ed 0%,#ede8dc 100%);
  background-size: 220px, cover;
  background-blend-mode: overlay;
}

/* ── SIDEBAR ── */
.chat-sidebar {
  flex: 0 0 300px;
  background: rgba(255,255,255,0.88);
  backdrop-filter: blur(20px);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(201,161,74,0.18);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* ── MAIN PANEL ── */
.chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(20px);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(201,161,74,0.18);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  min-width: 0;
}

/* ============================================================
   CHAT HEADER
============================================================ */
.chat-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 20px;
  background: linear-gradient(135deg,
    rgba(247,231,198,0.97) 0%,
    rgba(232,214,170,0.95) 100%);
  border-bottom: 1px solid rgba(201,161,74,0.22);
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}
.chat-header::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url('assets/img/jatapahang.png') center/120px repeat;
  opacity: 0.04;
  pointer-events: none;
}

.ch-avatar {
  position: relative;
  flex-shrink: 0;
}
.ch-avatar img {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  object-fit: cover;
  border: 2.5px solid rgba(201,161,74,0.5);
  box-shadow: var(--shadow-sm);
  display: block;
}
.online-dot {
  position: absolute;
  bottom: 1px;
  right: 1px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #22c55e;
  border: 2.5px solid #fff;
  display: none;
  box-shadow: 0 0 0 2px rgba(34,197,94,0.25);
}
.online-dot.show { display: block; }

.ch-info { flex: 1; min-width: 0; }
.ch-info strong {
  display: block;
  font-size: 0.97rem;
  font-weight: 700;
  color: var(--navy);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
#statusLine {
  font-size: 0.73rem;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 1px;
}

/* Context card in header */
.ch-card {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255,255,255,0.85);
  border: 1px solid rgba(201,161,74,0.3);
  border-radius: var(--radius-md);
  padding: 8px 12px;
  text-decoration: none;
  color: inherit;
  max-width: 215px;
  flex-shrink: 0;
  transition: var(--transition);
  backdrop-filter: blur(8px);
}
.ch-card:hover {
  background: #fff;
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
.ch-card img {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  flex-shrink: 0;
}
.ch-card-tag {
  font-size: 0.63rem;
  font-weight: 800;
  color: var(--gold-dark);
  text-transform: uppercase;
  letter-spacing: 0.7px;
}
.ch-card-name {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--navy);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 130px;
}
.ch-card-sub {
  font-size: 0.7rem;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 130px;
}
.ch-card-price {
  font-size: 0.83rem;
  font-weight: 800;
  color: var(--gold);
}

/* ============================================================
   CHAT BOX
============================================================ */
.chat-box {
  flex: 1;
  overflow-y: auto;
  padding: 18px 20px 12px;
  display: flex;
  flex-direction: column;
  gap: 0;
  scroll-behavior: smooth;
  background:
    radial-gradient(ellipse at 15% 10%, rgba(247,231,198,0.28) 0%, transparent 55%),
    radial-gradient(ellipse at 85% 85%, rgba(0,51,153,0.05) 0%, transparent 50%),
    transparent;
}
.chat-box::-webkit-scrollbar { width: 5px; }
.chat-box::-webkit-scrollbar-track { background: transparent; }
.chat-box::-webkit-scrollbar-thumb { background: rgba(201,161,74,0.3); border-radius: 10px; }

/* ============================================================
   DATE DIVIDER
============================================================ */
.date-div {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 14px 0 10px;
  position: relative;
}
.date-div::before {
  content: '';
  position: absolute;
  left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg,transparent,rgba(201,161,74,0.25),transparent);
}
.date-div span {
  position: relative;
  background: rgba(247,231,198,0.85);
  color: #7a5c1e;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 4px 16px;
  border-radius: 20px;
  border: 1px solid rgba(201,161,74,0.25);
  letter-spacing: 0.3px;
  backdrop-filter: blur(4px);
}

/* ============================================================
   MESSAGE GROUPS
============================================================ */
.msg-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 4px;
  animation: msgIn 0.22s cubic-bezier(.34,1.2,.64,1) both;
}
@keyframes msgIn {
  from { opacity: 0; transform: translateY(8px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.msg-group.me    { align-items: flex-end; }
.msg-group.other { align-items: flex-start; }
.msg-group.sys   { align-items: center; }

/* ── TEXT BUBBLE ── */
.bubble {
  display: inline-block;
  max-width: 65%;
  padding: 9px 15px;
  border-radius: 18px;
  font-size: 0.9rem;
  line-height: 1.55;
  color: var(--text);
  word-break: break-word;
  white-space: pre-wrap;
  margin-bottom: 2px;
}
.bubble.me {
  background: var(--bg-bubble-me);
  color: #fff;
  border-bottom-right-radius: 5px;
  box-shadow: 0 2px 8px rgba(184,149,68,0.3);
}
.bubble.other {
  background: var(--bubble-other);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  border-bottom-left-radius: 5px;
}
.bubble.sys {
  background: rgba(247,231,198,0.7);
  border: 1px solid rgba(201,161,74,0.2);
  color: var(--text-muted);
  font-size: 0.78rem;
  font-style: italic;
  max-width: 100%;
  text-align: center;
  padding: 6px 18px;
  border-radius: 20px;
  margin: 6px 0;
}
.bubble.sys a { color: var(--gold); text-decoration: underline; font-style: normal; font-weight: 600; }

/* Grouping shape refinement */
.msg-group.me    .bubble:not(:last-of-type) { border-bottom-right-radius: 5px; border-top-right-radius: 5px; }
.msg-group.me    .bubble:not(:first-of-type){ border-top-right-radius: 5px; }
.msg-group.other .bubble:not(:last-of-type) { border-bottom-left-radius: 5px; border-top-left-radius: 5px; }
.msg-group.other .bubble:not(:first-of-type){ border-top-left-radius: 5px; }

/* Timestamp row */
.bubble-meta {
  font-size: 0.67rem;
  color: var(--text-muted);
  margin-top: 1px;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.msg-group.me    .bubble-meta { justify-content: flex-end; margin-right: 3px; }
.msg-group.other .bubble-meta { justify-content: flex-start; margin-left: 3px; }
.read-tick { color: var(--gold); font-size: 0.68rem; }

/* ── IMAGE BUBBLE ── */
.img-bubble {
  display: inline-block;
  max-width: 260px;
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: zoom-in;
  position: relative;
  margin-bottom: 2px;
  box-shadow: var(--shadow-md);
  transition: transform var(--transition), box-shadow var(--transition);
}
.img-bubble:hover { transform: scale(1.02); box-shadow: var(--shadow-lg); }
.img-bubble img {
  width: 100%;
  display: block;
  max-height: 280px;
  object-fit: cover;
}
.img-bubble.me    { border: 3px solid rgba(201,161,74,0.45); }
.img-bubble.other { border: 3px solid rgba(0,0,0,0.06); }
.img-hover-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background var(--transition);
}
.img-bubble:hover .img-hover-overlay { background: rgba(0,0,0,0.2); }
.img-hover-overlay i {
  color: #fff;
  font-size: 1.7rem;
  opacity: 0;
  transform: scale(0.7);
  transition: opacity var(--transition), transform var(--transition);
}
.img-bubble:hover .img-hover-overlay i { opacity: 1; transform: scale(1); }

/* ── FILE BUBBLE ── */
.file-bubble {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  max-width: 310px;
  text-decoration: none;
  margin-bottom: 2px;
  transition: transform var(--transition), box-shadow var(--transition);
}
.file-bubble:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.file-bubble.me {
  background: var(--bg-bubble-me);
  border-bottom-right-radius: 5px;
  box-shadow: 0 2px 8px rgba(184,149,68,0.3);
}
.file-bubble.other {
  background: var(--bubble-other);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  border-bottom-left-radius: 5px;
}
.file-icon-box {
  width: 42px; height: 42px;
  border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.file-bubble.me    .file-icon-box { background: rgba(255,255,255,0.2); }
.file-bubble.other .file-icon-box { background: var(--gold-faint); }
.file-info { flex: 1; min-width: 0; }
.file-fname {
  font-size: 0.83rem; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 180px;
}
.file-bubble.me    .file-fname { color: #fff; }
.file-bubble.other .file-fname { color: var(--text); }
.file-fsize { font-size: 0.7rem; margin-top: 2px; }
.file-bubble.me    .file-fsize { color: rgba(255,255,255,0.72); }
.file-bubble.other .file-fsize { color: var(--text-muted); }
.file-dl {
  flex-shrink: 0; font-size: 0.95rem;
  transition: transform var(--transition);
}
.file-bubble.me    .file-dl { color: rgba(255,255,255,0.85); }
.file-bubble.other .file-dl { color: var(--gold); }
.file-bubble:hover .file-dl { transform: translateY(2px); }

/* ── CARD BUBBLE ── */
.card-bubble {
  display: inline-block;
  max-width: 285px;
  background: var(--bubble-other);
  border: 1px solid rgba(201,161,74,0.3);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-md);
  text-decoration: none;
  color: inherit;
  margin-bottom: 2px;
  transition: box-shadow var(--transition), transform var(--transition);
}
.card-bubble:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
.card-tag {
  background: linear-gradient(135deg, var(--gold-light), #e6d3a3);
  color: #7a5c1e;
  font-size: 0.68rem; font-weight: 800;
  padding: 6px 12px;
  display: flex; align-items: center; gap: 5px;
}
.card-body { display: flex; gap: 10px; padding: 12px; align-items: flex-start; }
.card-body img {
  width: 62px; height: 62px; border-radius: var(--radius-sm);
  object-fit: cover; flex-shrink: 0;
}
.card-details { flex: 1; min-width: 0; }
.card-nama {
  font-size: 0.82rem; font-weight: 700; color: var(--navy);
  line-height: 1.35; margin-bottom: 3px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.card-biz { font-size: 0.7rem; color: var(--text-muted); margin-bottom: 3px; }
.card-price { font-size: 0.9rem; font-weight: 800; color: var(--gold); }
.card-cta {
  display: inline-flex; align-items: center; gap: 4px;
  margin-top: 8px; padding: 5px 12px;
  background: var(--bg-bubble-me);
  color: #fff; border-radius: 20px;
  font-size: 0.7rem; font-weight: 700;
  transition: opacity var(--transition);
}
.card-bubble:hover .card-cta { opacity: 0.85; }

/* ── TYPING INDICATOR ── */
.typing-wrap { margin-bottom: 6px; }
.typing-dots {
  display: none;
  align-items: center; gap: 4px;
  padding: 10px 15px;
  background: var(--bubble-other);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  border-radius: 18px;
  border-bottom-left-radius: 5px;
  width: fit-content;
}
.typing-dots.show { display: inline-flex; }
.td { width: 7px; height: 7px; border-radius: 50%; background: #c0b080; animation: tdBounce 1.2s infinite; }
.td:nth-child(2) { animation-delay: .2s; }
.td:nth-child(3) { animation-delay: .4s; }
@keyframes tdBounce {
  0%,60%,100% { transform: translateY(0); opacity: .45; }
  30%         { transform: translateY(-6px); opacity: 1; }
}

/* ============================================================
   FILE PREVIEW BAR
============================================================ */
.file-preview-bar {
  display: none;
  align-items: center;
  gap: 14px;
  padding: 10px 18px;
  background: linear-gradient(90deg, rgba(247,231,198,0.9), rgba(240,220,170,0.85));
  border-top: 1px solid rgba(201,161,74,0.22);
  flex-shrink: 0;
  animation: slideUp .2s ease both;
}
.file-preview-bar.show { display: flex; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.fpb-thumb img {
  width: 52px; height: 52px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  border: 2px solid rgba(201,161,74,0.4);
  display: block;
}
.fpb-icon {
  width: 52px; height: 52px;
  border-radius: var(--radius-sm);
  background: rgba(255,255,255,0.7);
  border: 2px solid rgba(201,161,74,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem;
}
.fpb-info { flex: 1; min-width: 0; }
.fpb-label { font-size: 0.65rem; font-weight: 800; color: var(--gold-dark); text-transform: uppercase; letter-spacing: 0.8px; }
.fpb-name  { font-size: 0.85rem; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
.fpb-size  { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; }
.fpb-remove {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(220,38,38,0.1);
  border: 1.5px solid rgba(220,38,38,0.2);
  color: #dc2626;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 0.82rem;
  transition: background var(--transition);
  flex-shrink: 0;
}
.fpb-remove:hover { background: rgba(220,38,38,0.2); }

/* Upload progress */
.upload-progress {
  display: none;
  flex: 1; flex-direction: column; gap: 4px;
}
.upload-progress.show { display: flex; }
.up-bar   { height: 4px; background: rgba(201,161,74,0.2); border-radius: 10px; overflow: hidden; }
.up-fill  { height: 100%; background: var(--bg-bubble-me); border-radius: 10px; width: 0%; transition: width 0.3s ease; }
.up-label { font-size: 0.7rem; color: var(--text-muted); }

/* ============================================================
   INPUT AREA
============================================================ */
.chat-input-area {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px 12px;
  background: rgba(255,255,255,0.9);
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}

/* Attach */
.attach-btn {
  position: relative;
  width: 44px; height: 44px;
  border-radius: 50%;
  background: var(--gold-faint);
  border: 2px solid rgba(201,161,74,0.28);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: var(--gold-dark);
  font-size: 1.05rem;
  transition: all var(--transition);
  flex-shrink: 0;
}
.attach-btn:hover { background: rgba(201,161,74,0.22); transform: rotate(20deg); }
.attach-btn input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}

/* Text input */
.input-row {
  flex: 1;
  display: flex;
  align-items: center;
  background: #f8f6f1;
  border: 2px solid rgba(201,161,74,0.28);
  border-radius: 24px;
  padding: 7px 16px;
  gap: 8px;
  transition: border-color var(--transition), box-shadow var(--transition);
}
.input-row:focus-within {
  border-color: var(--gold);
  box-shadow: 0 0 0 4px rgba(201,161,74,0.1);
  background: #fff;
}
.input-row input {
  flex: 1; border: none; outline: none;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.92rem;
  background: transparent;
  color: var(--text);
  padding: 5px 0;
}
.input-row input::placeholder { color: #b5a998; }

/* Send button */
.send-btn {
  width: 46px; height: 46px;
  border-radius: 50%;
  background: var(--bg-bubble-me);
  border: none;
  color: #fff;
  font-size: 1rem;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 14px rgba(201,161,74,0.38);
  flex-shrink: 0;
  transition: transform var(--transition), box-shadow var(--transition);
}
.send-btn:hover  { transform: scale(1.08); box-shadow: 0 6px 20px rgba(201,161,74,0.48); }
.send-btn:active { transform: scale(0.95); }
.send-btn.busy   { opacity: 0.65; pointer-events: none; }

/* ============================================================
   EMPTY / NO CHAT
============================================================ */
.no-chat {
  flex: 1;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 14px;
  color: var(--text-muted);
  background: radial-gradient(ellipse at 50% 40%, rgba(247,231,198,0.3), transparent 70%);
}
.no-chat .nc-icon {
  width: 80px; height: 80px;
  border-radius: 50%;
  background: rgba(201,161,74,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem;
  color: rgba(201,161,74,0.4);
}
.no-chat p { font-size: 0.88rem; font-weight: 500; }

/* ============================================================
   LIGHTBOX
============================================================ */
.lightbox {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.92);
  z-index: 9999;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  opacity: 0; pointer-events: none;
  transition: opacity 0.3s ease;
}
.lightbox.open { opacity: 1; pointer-events: all; }

.lb-img-wrap { position: relative; }
.lb-img-wrap img {
  max-width: 90vw; max-height: 82vh;
  border-radius: var(--radius-lg);
  box-shadow: 0 30px 80px rgba(0,0,0,0.6);
  display: block;
  transform: scale(0.88);
  transition: transform 0.35s cubic-bezier(.34,1.4,.64,1);
}
.lightbox.open .lb-img-wrap img { transform: scale(1); }

.lb-close {
  position: absolute;
  top: -14px; right: -14px;
  width: 34px; height: 34px;
  border-radius: 50%;
  background: #fff;
  border: none;
  color: var(--navy);
  font-size: 0.88rem;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  transition: transform var(--transition);
}
.lb-close:hover { transform: rotate(90deg) scale(1.1); }

.lb-dl {
  margin-top: 18px;
  display: flex; align-items: center; gap: 8px;
  padding: 9px 24px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.18);
  color: #fff;
  border-radius: 30px;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 600;
  backdrop-filter: blur(6px);
  transition: background var(--transition);
}
.lb-dl:hover { background: rgba(255,255,255,0.2); color: #fff; }

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 840px) {
  .chat-wrap    { top: 80px; padding: 8px; gap: 0; }
  .chat-sidebar { display: none; }
  .chat-panel   { border-radius: 16px; }
  .ch-card      { display: none; }
}
</style>
</head>
<body>

<div class="chat-wrap">

  <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
  <div class="chat-sidebar">
    <?php include "chat_list.php"; ?>
  </div>

  <!-- ══ MAIN PANEL ════════════════════════════════════════ -->
  <div class="chat-panel">

    <?php if ($no_chat): ?>

      <div class="chat-header">
        <div class="ch-info"><strong>Chat</strong></div>
      </div>
      <div class="no-chat">
        <div class="nc-icon"><i class="fas fa-comments"></i></div>
        <p>Pilih perbualan dari senarai untuk mula berbual</p>
      </div>

    <?php else: ?>

    <!-- HEADER -->
    <div class="chat-header">
      <div class="ch-avatar">
        <img id="partnerAvatar"
             src="<?= htmlspecialchars($partner_avatar) ?>"
             onerror="this.src='assets/img/default_avatar.jpg'">
        <span class="online-dot" id="onlineDot"></span>
      </div>
      <div class="ch-info">
        <strong><?= htmlspecialchars($partner_name) ?></strong>
        <span id="statusLine">
          <i class="fas fa-circle" style="font-size:.42rem;color:#cbd5e1"></i>
          Menyemak status...
        </span>
      </div>

      <?php if ($ctx_card): ?>
      <a href="<?= htmlspecialchars($ctx_card['url']) ?>" class="ch-card" target="_blank">
        <img src="<?= htmlspecialchars($ctx_card['gambar']) ?>"
             onerror="this.src='assets/img/default_avatar.jpg'">
        <div>
          <div class="ch-card-tag">
            <?= $ctx_card['type']==='produk' ? '📦 Produk' : '🛠 Servis' ?>
          </div>
          <div class="ch-card-name"><?= htmlspecialchars($ctx_card['nama']) ?></div>
          <?php if (!empty($ctx_card['perniagaan'])): ?>
            <div class="ch-card-sub">🏪 <?= htmlspecialchars($ctx_card['perniagaan']) ?></div>
          <?php endif; ?>
          <?php if (isset($ctx_card['harga'])): ?>
            <div class="ch-card-price">RM <?= number_format($ctx_card['harga'],2) ?></div>
          <?php endif; ?>
        </div>
      </a>
      <?php endif; ?>
    </div><!-- /chat-header -->

    <!-- CHAT BOX -->
    <div class="chat-box" id="chatBox">
      <!-- Typing indicator (inserted before messages via JS anchor) -->
      <div class="msg-group other typing-wrap" id="typingWrap" style="display:none;">
        <div class="typing-dots" id="typingDots">
          <div class="td"></div>
          <div class="td"></div>
          <div class="td"></div>
        </div>
      </div>
    </div>

    <!-- FILE PREVIEW BAR -->
    <div class="file-preview-bar" id="filePreviewBar">
      <div id="fpbVisual"></div>

      <!-- Normal preview info -->
      <div class="fpb-info" id="fpbInfo">
        <div class="fpb-label">Fail dipilih</div>
        <div class="fpb-name" id="fpbName">—</div>
        <div class="fpb-size" id="fpbSize"></div>
      </div>

      <!-- Upload progress (shown when uploading) -->
      <div class="upload-progress" id="uploadProgress">
        <div class="up-label" id="upLabel">Memuat naik…</div>
        <div class="up-bar"><div class="up-fill" id="upFill"></div></div>
      </div>

      <button class="fpb-remove" id="fpbRemove" onclick="removeFile()" title="Buang fail">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- INPUT AREA -->
    <div class="chat-input-area">
      <!-- Attach -->
      <button class="attach-btn" title="Lampirkan fail">
        <i class="fas fa-paperclip"></i>
        <input type="file" id="fileInput" accept="*/*" onchange="onFileSelected(event)">
      </button>

      <!-- Text -->
      <div class="input-row">
        <input type="text" id="msgInput"
               placeholder="Taip mesej…"
               autocomplete="off"
               onkeydown="if(event.key==='Enter'&&!event.shiftKey){doSend();event.preventDefault();}">
      </div>

      <!-- Send -->
      <button class="send-btn" id="sendBtn" onclick="doSend()" title="Hantar">
        <i class="fas fa-paper-plane" id="sendIcon"></i>
      </button>
    </div>

    <?php endif; ?>
  </div><!-- /chat-panel -->
</div><!-- /chat-wrap -->

<!-- ══ LIGHTBOX ═════════════════════════════════════════════ -->
<div class="lightbox" id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <div class="lb-img-wrap">
    <button class="lb-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <img id="lbImg" src="" alt="">
  </div>
  <a id="lbDl" href="#" download target="_blank" class="lb-dl">
    <i class="fas fa-download"></i> Muat Turun
  </a>
</div>

<script>
/* ============================================================
   CONSTANTS
============================================================ */
const CHAT_ID    = <?= $no_chat ? 0 : $chat_id ?>;
const PARTNER_ID = <?= isset($partner_id) ? (int)$partner_id : 0 ?>;
const IMG_MIMES  = new Set(['image/jpeg','image/png','image/gif','image/webp','image/svg+xml']);

/* ============================================================
   STATE
============================================================ */
let lastMsgId        = 0;
let renderedSet      = new Set();
let lastDateKey      = null;
let lastGroupSender  = null;  // 'me'|'other'|null
let lastGroupEl      = null;  // last group DOM element
let pendingFile      = null;  // { file, isImg }

/* ============================================================
   UTILITIES
============================================================ */
function fileEmoji(name, mime) {
  if (IMG_MIMES.has(mime)) return '🖼️';
  const ext = (name||'').split('.').pop().toLowerCase();
  return ({
    pdf:'📄', doc:'📝', docx:'📝',
    xls:'📊', xlsx:'📊', ppt:'📊', pptx:'📊',
    zip:'🗜️', rar:'🗜️', '7z':'🗜️',
    mp4:'🎥', mov:'🎥', avi:'🎥', mkv:'🎥',
    mp3:'🎵', wav:'🎵', aac:'🎵',
    txt:'📃', csv:'📊'
  })[ext] || '📎';
}

function fmtBytes(b) {
  if (!b) return '';
  if (b < 1024)      return b + ' B';
  if (b < 1048576)   return (b / 1024).toFixed(1) + ' KB';
  return (b / 1048576).toFixed(1) + ' MB';
}

function humanDate(raw) {
  const d   = new Date(raw);
  if (isNaN(d)) return raw;
  const now  = new Date();
  const diff = Math.floor((now - d) / 86400000);
  if (diff === 0) return 'Hari Ini';
  if (diff === 1) return 'Semalam';
  const days = ['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
  if (diff < 7) return days[d.getDay()];
  const mo = ['Jan','Feb','Mac','Apr','Mei','Jun','Jul','Ogos','Sep','Okt','Nov','Dis'];
  return `${d.getDate()} ${mo[d.getMonth()]} ${d.getFullYear()}`;
}

function dateKey(raw) {
  const d = new Date(raw);
  return isNaN(d) ? raw : `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
}

/* ============================================================
   FILE SELECTION → PREVIEW BAR
============================================================ */
function onFileSelected(e) {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 20 * 1024 * 1024) {
    alert('Saiz fail melebihi had 20MB. Sila pilih fail yang lebih kecil.');
    e.target.value = '';
    return;
  }

  const bar   = document.getElementById('filePreviewBar');
  const vis   = document.getElementById('fpbVisual');
  const isImg = file.type.startsWith('image/');

  document.getElementById('fpbName').textContent = file.name;
  document.getElementById('fpbSize').textContent = fmtBytes(file.size);
  document.getElementById('fpbInfo').style.display = '';
  document.getElementById('uploadProgress').classList.remove('show');

  if (isImg) {
    const reader = new FileReader();
    reader.onload = ev => {
      vis.innerHTML = `<div class="fpb-thumb"><img src="${ev.target.result}" alt=""></div>`;
      pendingFile = { file, isImg: true };
      bar.classList.add('show');
    };
    reader.readAsDataURL(file);
  } else {
    vis.innerHTML = `<div class="fpb-icon">${fileEmoji(file.name, file.type)}</div>`;
    pendingFile = { file, isImg: false };
    bar.classList.add('show');
  }

  document.getElementById('msgInput').focus();
}

function removeFile() {
  pendingFile = null;
  document.getElementById('fileInput').value = '';
  document.getElementById('filePreviewBar').classList.remove('show');
  document.getElementById('uploadProgress').classList.remove('show');
}

/* ============================================================
   BUILD BUBBLE ELEMENT
============================================================ */
function buildBubble(m) {
  const side = m.is_me ? 'me' : 'other';
  const type = m.message_type || 'text';

  // IMAGE
  if (type === 'image' && m.file_url) {
    const wrap = document.createElement('div');
    wrap.className = `img-bubble ${side}`;
    wrap.innerHTML = `
      <img src="${m.file_url}" alt="Gambar" loading="lazy"
           onerror="this.src='assets/img/no-image.png'">
      <div class="img-hover-overlay"><i class="fas fa-search-plus"></i></div>
    `;
    wrap.onclick = () => openLightbox(m.file_url);
    return wrap;
  }

  // FILE
  if (type === 'file' && m.file_url) {
    const a = document.createElement('a');
    a.className = `file-bubble ${side}`;
    a.href = m.file_url;
    a.target = '_blank';
    a.download = m.file_name || 'fail';
    a.innerHTML = `
      <div class="file-icon-box">${fileEmoji(m.file_name, m.file_mime)}</div>
      <div class="file-info">
        <div class="file-fname">${m.file_name || 'Fail'}</div>
        <div class="file-fsize">${fmtBytes(m.file_size)}</div>
      </div>
      <div class="file-dl"><i class="fas fa-download"></i></div>
    `;
    return a;
  }

  // CARD / SERVIS
  if ((type==='card'||type==='servis') && m.card) {
    const c = m.card, isSv = (c.type==='servis');
    const a = document.createElement('a');
    a.className = 'card-bubble';
    a.href = c.url; a.target = '_blank';
    a.innerHTML = `
      <div class="card-tag">${isSv ? '🛠 Servis' : '📦 Produk'}</div>
      <div class="card-body">
        <img src="${c.gambar}" onerror="this.src='assets/img/default_avatar.jpg'">
        <div class="card-details">
          <div class="card-nama">${c.nama}</div>
          ${c.perniagaan ? `<div class="card-biz">🏪 ${c.perniagaan}</div>` : ''}
          ${!isSv
            ? `<div class="card-price">RM ${parseFloat(c.harga||0).toFixed(2)}</div>`
            : `<div style="font-size:.7rem;color:var(--text-muted)">📍 ${c.lokasi||''}</div>`}
          <span class="card-cta">Lihat ${isSv?'Servis':'Produk'}
            <i class="fas fa-arrow-right" style="font-size:.55rem"></i>
          </span>
        </div>
      </div>
    `;
    return a;
  }

  // SYSTEM
  if (m.sender_id == 0 || type === 'system') {
    const b = document.createElement('div');
    b.className = 'bubble sys';
    b.innerHTML = m.message;
    return b;
  }

  // TEXT
  const b = document.createElement('div');
  b.className = `bubble ${side}`;
  b.textContent = m.message;
  return b;
}

/* ============================================================
   RENDER MESSAGES
============================================================ */
function renderMessages(msgs) {
  const box = document.getElementById('chatBox');
  const twrap = document.getElementById('typingWrap');

  msgs.forEach(m => {
    if (renderedSet.has(m.id)) return;
    renderedSet.add(m.id);

    const type  = m.message_type || 'text';
    const isSys = (m.sender_id == 0 || type === 'system');
    const side  = m.is_me ? 'me' : 'other';

    /* ── Date divider ── */
    const dk = dateKey(m.date_raw || m.time);
    if (dk !== lastDateKey) {
      lastDateKey      = dk;
      lastGroupSender  = null;
      lastGroupEl      = null;
      const dd = document.createElement('div');
      dd.className = 'date-div';
      dd.innerHTML = `<span>${humanDate(m.date_raw || m.time)}</span>`;
      box.insertBefore(dd, twrap);
    }

    const groupable = (type === 'text' && !isSys);

    let group;
    if (!groupable) {
      /* Non-groupable: always new container */
      group = document.createElement('div');
      group.className = isSys ? 'msg-group sys' : `msg-group ${side}`;
      box.insertBefore(group, twrap);
      lastGroupSender = null;
      lastGroupEl     = null;
    } else {
      if (lastGroupSender === side && lastGroupEl) {
        /* Same sender — append to existing group */
        group = lastGroupEl;
        /* Remove old timestamp from previous last-bubble */
        const oldMeta = group.querySelector('.bubble-meta');
        if (oldMeta) oldMeta.remove();
      } else {
        /* New group */
        group = document.createElement('div');
        group.className = `msg-group ${side}`;
        box.insertBefore(group, twrap);
        lastGroupSender = side;
        lastGroupEl     = group;
      }
    }

    group.appendChild(buildBubble(m));

    /* Timestamp */
    if (!isSys) {
      const meta = document.createElement('div');
      meta.className = 'bubble-meta';
      meta.innerHTML = m.is_me
        ? `${m.time}&nbsp;<span class="read-tick"><i class="fas fa-check-double"></i></span>`
        : m.time;
      group.appendChild(meta);
    }

    lastMsgId = Math.max(lastMsgId, m.id);
  });

  box.scrollTop = box.scrollHeight;
}

/* ============================================================
   LOAD MESSAGES (poll)
============================================================ */
function loadMessages() {
  fetch(`load_messages.php?chat_id=${CHAT_ID}&last_id=${lastMsgId}`)
    .then(r => r.json())
    .then(msgs => { if (msgs.length) renderMessages(msgs); })
    .catch(() => {});
}

/* ============================================================
   SEND
============================================================ */
function setBusy(on) {
  const btn  = document.getElementById('sendBtn');
  const icon = document.getElementById('sendIcon');
  btn.classList.toggle('busy', on);
  icon.className = on ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane';
}

function doSend() {
  const input = document.getElementById('msgInput');
  const text  = input.value.trim();

  if (!text && !pendingFile) return;

  /* First message → create room */
  if (CHAT_ID === 0) {
    const f = document.createElement('form');
    f.method = 'POST'; f.action = 'create_chat.php';
    f.enctype = 'multipart/form-data';
    [['partner_id', PARTNER_ID], ['message', text]].forEach(([n,v]) => {
      const i = document.createElement('input'); i.name = n; i.value = v; f.appendChild(i);
    });
    document.body.appendChild(f); f.submit(); return;
  }

  /* File upload */
  if (pendingFile) {
    setBusy(true);

    // Show progress in preview bar
    const info = document.getElementById('fpbInfo');
    const prog = document.getElementById('uploadProgress');
    const fill = document.getElementById('upFill');
    const lbl  = document.getElementById('upLabel');
    info.style.display = 'none';
    document.getElementById('fpbRemove').style.display = 'none';
    prog.classList.add('show');

    const fd = new FormData();
    fd.append('chat_id', CHAT_ID);
    fd.append('file',    pendingFile.file);
    if (text) fd.append('message', text);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'send_message.php');

    xhr.upload.onprogress = e => {
      if (e.lengthComputable) {
        const pct = Math.round((e.loaded / e.total) * 100);
        fill.style.width = pct + '%';
        lbl.textContent  = `Memuat naik… ${pct}%`;
      }
    };

    xhr.onload = () => {
      setBusy(false);
      document.getElementById('fpbRemove').style.display = '';
      prog.classList.remove('show');
      info.style.display = '';

      if (xhr.responseText.trim() === 'OK') {
        removeFile();
        input.value = '';
        loadMessages();
      } else {
        alert('Ralat menghantar fail: ' + xhr.responseText);
      }
    };

    xhr.onerror = () => {
      setBusy(false);
      alert('Ralat sambungan semasa memuat naik.');
    };

    xhr.send(fd);
    return;
  }

  /* Text only */
  const msg = text;
  input.value = '';

  fetch('send_message.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    `chat_id=${CHAT_ID}&message=${encodeURIComponent(msg)}`
  }).then(() => loadMessages());
}

/* ============================================================
   LIGHTBOX
============================================================ */
function openLightbox(src) {
  document.getElementById('lbImg').src = src;
  document.getElementById('lbDl').href  = src;
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key==='Escape') closeLightbox(); });

/* ============================================================
   ONLINE STATUS
============================================================ */
function updateStatus() {
  fetch(`check_status.php?chat_id=${CHAT_ID}`)
    .then(r => r.text()).then(s => {
      const online = (s.trim() === 'online');
      const dot = document.getElementById('onlineDot');
      const el  = document.getElementById('statusLine');
      if (dot) dot.classList.toggle('show', online);
      if (el) el.innerHTML = online
        ? '<i class="fas fa-circle" style="font-size:.42rem;color:#22c55e"></i> Online'
        : '<i class="fas fa-circle" style="font-size:.42rem;color:#cbd5e1"></i> Offline';
    }).catch(() => {});
}

/* ============================================================
   TYPING INDICATOR
============================================================ */
function checkTyping() {
  fetch(`check_typing.php?chat_id=${CHAT_ID}`)
    .then(r => r.text()).then(s => {
      const wrap = document.getElementById('typingWrap');
      const dots = document.getElementById('typingDots');
      if (!wrap) return;
      const isTyping = (s.trim() === 'typing');
      wrap.style.display = isTyping ? 'flex' : 'none';
      if (isTyping) dots.classList.add('show');
      else          dots.classList.remove('show');
    }).catch(() => {});
}

let typingTimer;
document.getElementById('msgInput')?.addEventListener('input', () => {
  clearTimeout(typingTimer);
  fetch('update_typing.php', {
    method:  'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded' },
    body:    `chat_id=${CHAT_ID}`
  });
  typingTimer = setTimeout(() => {}, 3000);
});

/* ============================================================
   INIT
============================================================ */
if (CHAT_ID > 0) {
  loadMessages();
  updateStatus();

  setInterval(loadMessages,  2000);
  setInterval(updateStatus,  5000);
  setInterval(checkTyping,   1500);
}
</script>

<!--<?php include "footer.php"; ?>-->
</body>
</html>