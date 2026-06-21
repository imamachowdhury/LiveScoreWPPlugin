<?php
defined( 'ABSPATH' ) || exit;
$match = get_query_var( 'lsb_match_data' );

// Overlay position: ?pos=top-left | top-right | bottom-left | bottom-right | top-center | bottom-center
$pos   = in_array( $_GET['pos'] ?? '', array( 'top-left','top-right','bottom-left','bottom-right','top-center','bottom-center' ), true )
         ? $_GET['pos'] : 'bottom-left';

// Theme: ?theme=dark | light | glass
$theme = in_array( $_GET['theme'] ?? '', array( 'dark','light','glass' ), true )
         ? $_GET['theme'] : 'dark';

$ajax_url = admin_url( 'admin-ajax.php' );
$match_id = (int) $match->id;
$sport    = $match->sport;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $match->title ); ?> — Overlay</title>
<style>
/* ── Reset ───────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    width: 1920px; height: 1080px;
    background: transparent !important;
    overflow: hidden;
    font-family: 'Segoe UI', 'Arial', sans-serif;
}

/* ── Position helpers ─────────────────────────────────────────────────── */
#lsb-overlay {
    position: absolute;
    z-index: 9999;
}
.pos-bottom-left   { bottom: 40px; left: 40px; }
.pos-bottom-right  { bottom: 40px; right: 40px; }
.pos-top-left      { top: 40px; left: 40px; }
.pos-top-right     { top: 40px; right: 40px; }
.pos-top-center    { top: 40px; left: 50%; transform: translateX(-50%); }
.pos-bottom-center { bottom: 40px; left: 50%; transform: translateX(-50%); }

/* ── Themes ───────────────────────────────────────────────────────────── */
/* DARK */
.theme-dark  { --bg1: rgba(10,10,30,0.92); --bg2: rgba(20,20,55,0.88); --accent: #e94560; --text: #fff; --sub: rgba(255,255,255,0.55); --border: rgba(255,255,255,0.08); --live-dot: #ff3b3b; }
/* LIGHT */
.theme-light { --bg1: rgba(255,255,255,0.93); --bg2: rgba(240,242,250,0.90); --accent: #d0021b; --text: #111; --sub: rgba(0,0,0,0.50); --border: rgba(0,0,0,0.10); --live-dot: #d0021b; }
/* GLASS */
.theme-glass { --bg1: rgba(255,255,255,0.12); --bg2: rgba(255,255,255,0.07); --accent: #00e5ff; --text: #fff; --sub: rgba(255,255,255,0.60); --border: rgba(255,255,255,0.20); --live-dot: #00e5ff; backdrop-filter: blur(18px) saturate(180%); -webkit-backdrop-filter: blur(18px) saturate(180%); }

/* ── Card shell ───────────────────────────────────────────────────────── */
.lsb-card {
    background: var(--bg1);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    min-width: 420px;
    box-shadow: 0 12px 48px rgba(0,0,0,0.55);
}

/* ── Top bar ──────────────────────────────────────────────────────────── */
.lsb-topbar {
    background: var(--bg2);
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 18px;
    border-bottom: 1px solid var(--border);
}
.lsb-match-title {
    color: var(--sub);
    font-size: 13px; font-weight: 600; letter-spacing: 0.6px; text-transform: uppercase;
}
.lsb-live-badge {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--live-dot);
}
.lsb-live-badge .dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--live-dot);
    animation: pulse 1.4s ease-in-out infinite;
}
.lsb-upcoming-badge { color: var(--sub); font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
.lsb-ft-badge       { color: var(--sub); font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }

@keyframes pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.4; transform: scale(0.7); }
}

/* ── Football layout ─────────────────────────────────────────────────── */
.lsb-football-body {
    display: flex; align-items: center;
    padding: 18px 22px;
    gap: 0;
}
.lsb-ft-team {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px;
}
.lsb-ft-name {
    color: var(--sub); font-size: 12px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase; text-align: center;
    max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lsb-ft-score {
    color: var(--text); font-size: 56px; font-weight: 900; line-height: 1;
    font-variant-numeric: tabular-nums;
}
.lsb-ft-mid {
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    padding: 0 16px;
}
.lsb-ft-separator { color: var(--sub); font-size: 24px; font-weight: 900; }
.lsb-ft-clock {
    color: var(--accent); font-size: 22px; font-weight: 800;
    font-variant-numeric: tabular-nums;
}
.lsb-ft-half { color: var(--sub); font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }

/* ── Football goal flash ─────────────────────────────────────────────── */
.lsb-ft-score.goal-flash { animation: goalFlash 0.8s ease; }
@keyframes goalFlash {
    0%   { color: #ffe600; transform: scale(1.3); }
    100% { color: var(--text); transform: scale(1); }
}

/* ── Football divider line ───────────────────────────────────────────── */
.lsb-accent-bar {
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
}

/* ── Football events ticker ──────────────────────────────────────────── */
.lsb-ticker {
    background: var(--bg2);
    padding: 6px 18px;
    border-top: 1px solid var(--border);
    font-size: 12px; color: var(--sub);
    white-space: nowrap; overflow: hidden;
    min-height: 26px;
}

/* ── Cricket layout ──────────────────────────────────────────────────── */
.lsb-cricket-body {
    padding: 16px 22px 12px;
    display: flex; flex-direction: column; gap: 10px;
}
.lsb-ck-row {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.lsb-ck-team-name {
    color: var(--sub); font-size: 12px; font-weight: 700;
    letter-spacing: 0.8px; text-transform: uppercase;
    min-width: 110px; max-width: 140px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lsb-ck-score-block { display: flex; align-items: baseline; gap: 4px; }
.lsb-ck-runs  { color: var(--text); font-size: 36px; font-weight: 900; font-variant-numeric: tabular-nums; }
.lsb-ck-sep   { color: var(--sub); font-size: 24px; font-weight: 400; }
.lsb-ck-wkts  { color: var(--accent); font-size: 22px; font-weight: 800; }
.lsb-ck-overs { color: var(--sub); font-size: 13px; font-weight: 600; margin-left: 6px; }
.lsb-ck-batting-marker {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--accent); flex-shrink: 0;
}

.lsb-ck-divider { height: 1px; background: var(--border); }

.lsb-ck-meta {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 0 4px;
}
.lsb-ck-innings { color: var(--sub); font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; }
.lsb-ck-target  { color: var(--accent); font-size: 13px; font-weight: 800; }
.lsb-ck-result  {
    text-align: center; padding: 6px 18px 12px;
    color: var(--accent); font-size: 14px; font-weight: 800;
    letter-spacing: 0.5px;
}

/* ── Hidden state ────────────────────────────────────────────────────── */
#lsb-overlay.lsb-hidden { opacity: 0; pointer-events: none; }
#lsb-overlay { transition: opacity 0.4s ease; }
</style>
</head>
<body>

<div id="lsb-overlay" class="pos-<?php echo esc_attr( $pos ); ?> theme-<?php echo esc_attr( $theme ); ?> lsb-hidden">
    <div class="lsb-card" id="lsb-card">
        <!-- rendered by JS -->
    </div>
</div>

<script>
(function () {
    var AJAX    = <?php echo wp_json_encode( $ajax_url ); ?>;
    var MATCH   = <?php echo (int) $match_id; ?>;
    var SPORT   = <?php echo wp_json_encode( $sport ); ?>;
    var POLL_MS = 6000;

    var $overlay  = document.getElementById('lsb-overlay');
    var $card     = document.getElementById('lsb-card');
    var prevScore = {};

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = String(s || '');
        return d.innerHTML;
    }

    function badgeHtml(status) {
        if (status === 'live')     return '<span class="lsb-live-badge"><span class="dot"></span>LIVE</span>';
        if (status === 'upcoming') return '<span class="lsb-upcoming-badge">UPCOMING</span>';
        return '<span class="lsb-ft-badge">FULL TIME</span>';
    }

    function renderFootball(d) {
        var s = d.score || {};
        var scoreA = s.score_a || 0;
        var scoreB = s.score_b || 0;
        var flashA = scoreA !== (prevScore.score_a || 0) ? ' goal-flash' : '';
        var flashB = scoreB !== (prevScore.score_b || 0) ? ' goal-flash' : '';

        var midHtml = d.status === 'live'
            ? '<span class="lsb-ft-clock">' + (s.minute || 0) + '\'</span><span class="lsb-ft-half">' + esc(s.half || '') + '</span>'
            : '<span class="lsb-ft-separator">—</span>';

        var html = '<div class="lsb-topbar">'
            + '<span class="lsb-match-title">' + esc(d.title) + '</span>'
            + badgeHtml(d.status)
            + '</div>'
            + '<div class="lsb-accent-bar"></div>'
            + '<div class="lsb-football-body">'
            +   '<div class="lsb-ft-team">'
            +     '<span class="lsb-ft-name">' + esc(d.team_a) + '</span>'
            +     '<span class="lsb-ft-score' + flashA + '" id="lsb-sa">' + scoreA + '</span>'
            +   '</div>'
            +   '<div class="lsb-ft-mid">' + midHtml + '</div>'
            +   '<div class="lsb-ft-team">'
            +     '<span class="lsb-ft-name">' + esc(d.team_b) + '</span>'
            +     '<span class="lsb-ft-score' + flashB + '" id="lsb-sb">' + scoreB + '</span>'
            +   '</div>'
            + '</div>';

        if (s.events) {
            html += '<div class="lsb-ticker">' + esc(s.events) + '</div>';
        }
        return html;
    }

    function renderCricket(d) {
        var s = d.score || {};
        var battingA = (s.batting_team == 1);
        var battingB = (s.batting_team == 2);

        var html = '<div class="lsb-topbar">'
            + '<span class="lsb-match-title">' + esc(d.title) + '</span>'
            + badgeHtml(d.status)
            + '</div>'
            + '<div class="lsb-accent-bar"></div>'
            + '<div class="lsb-cricket-body">'

            // Team A row
            + '<div class="lsb-ck-row">'
            +   (battingA ? '<span class="lsb-ck-batting-marker"></span>' : '<span style="width:6px"></span>')
            +   '<span class="lsb-ck-team-name">' + esc(d.team_a) + '</span>'
            +   '<div class="lsb-ck-score-block">'
            +     '<span class="lsb-ck-runs">' + (s.score_a_runs || 0) + '</span>'
            +     '<span class="lsb-ck-sep">/</span>'
            +     '<span class="lsb-ck-wkts">' + (s.score_a_wkts || 0) + '</span>'
            +     '<span class="lsb-ck-overs">(' + (s.score_a_overs || 0) + ' ov)</span>'
            +   '</div>'
            + '</div>'

            + '<div class="lsb-ck-divider"></div>'

            // Team B row
            + '<div class="lsb-ck-row">'
            +   (battingB ? '<span class="lsb-ck-batting-marker"></span>' : '<span style="width:6px"></span>')
            +   '<span class="lsb-ck-team-name">' + esc(d.team_b) + '</span>'
            +   '<div class="lsb-ck-score-block">'
            +     '<span class="lsb-ck-runs">' + (s.score_b_runs || 0) + '</span>'
            +     '<span class="lsb-ck-sep">/</span>'
            +     '<span class="lsb-ck-wkts">' + (s.score_b_wkts || 0) + '</span>'
            +     '<span class="lsb-ck-overs">(' + (s.score_b_overs || 0) + ' ov)</span>'
            +   '</div>'
            + '</div>'

            // Meta row
            + '<div class="lsb-ck-meta">'
            +   '<span class="lsb-ck-innings">Innings ' + (s.innings || 1) + '</span>'
            +   (s.target ? '<span class="lsb-ck-target">Target: ' + s.target + '</span>' : '')
            + '</div>'
            + '</div>';

        if (s.result_text) {
            html += '<div class="lsb-ck-result">' + esc(s.result_text) + '</div>';
        }

        return html;
    }

    function poll() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', AJAX + '?action=lsb_get_score&match_id=' + MATCH, true);
        xhr.onload = function () {
            if (xhr.status !== 200) return;
            try {
                var res = JSON.parse(xhr.responseText);
                if (!res.success) return;
                var d = res.data;
                var html = SPORT === 'football' ? renderFootball(d) : renderCricket(d);
                $card.innerHTML = html;
                prevScore = d.score || {};
                $overlay.classList.remove('lsb-hidden');
            } catch (e) {}
        };
        xhr.send();
    }

    poll();
    setInterval(poll, POLL_MS);
}());
</script>
</body>
</html>
