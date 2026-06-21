/* global lsbConfig, jQuery */
(function ($) {
    'use strict';

    // ── Scoreboard polling ────────────────────────────────────────────────
    // The initial score is rendered server-side (always visible).
    // JS only polls to keep it live-updating.
    function initBoards() {
        $('.lsb-board').each(function () {
            var $board   = $(this);
            var matchId  = $board.data('match-id');
            if (!matchId) return;
            // Only poll on live or upcoming matches; finished boards don't need it
            var status = $board.find('.lsb-badge--finished').length ? 'finished' : 'active';
            if (status === 'finished') return;
            fetchAndRender($board, matchId);
            if (lsbConfig.pollInterval > 0) {
                setInterval(function () {
                    fetchAndRender($board, matchId);
                }, lsbConfig.pollInterval);
            }
        });
    }

    function fetchAndRender($board, matchId) {
        $.get(lsbConfig.ajaxUrl, {
            action: 'lsb_get_score',
            match_id: matchId,
            _: Date.now()
        }).done(function (res) {
            if (res.success) {
                $board.html(buildBoardHtml(res.data));
            }
        }).fail(function () {
            // Silently fail on network error — server-rendered score stays visible
        });
    }

    function buildBoardHtml(d) {
        var sport = d.sport;
        var statusLabel = lsbConfig.i18n[d.status] || d.status;
        var isLive = d.status === 'live';

        var html = '<div class="lsb-inner lsb-inner--' + sport + '">';
        html += '<div class="lsb-header">';
        html += '<span class="lsb-title">' + esc(d.title) + '</span>';
        html += '<span class="lsb-badge lsb-badge--' + esc(d.status) + '">' + (isLive ? '● ' : '') + esc(statusLabel) + '</span>';
        html += '</div>';

        if (sport === 'football') {
            html += buildFootballScore(d);
        } else {
            html += buildCricketScore(d);
        }
        html += '</div>';
        return html;
    }

    function buildFootballScore(d) {
        var s = d.score || {};
        var html = '<div class="lsb-football">';
        html += '<div class="lsb-teams">';
        html += '<div class="lsb-team">';
        html +=   '<span class="lsb-team-name">' + esc(d.team_a) + '</span>';
        html +=   '<span class="lsb-goal">' + (s.score_a || 0) + '</span>';
        html += '</div>';
        html += '<div class="lsb-separator">';
        if (d.status === 'live') {
            html += '<span class="lsb-minute">' + (s.minute || 0) + '\'</span>';
            html += '<span class="lsb-half">' + esc(s.half || '') + '</span>';
        } else {
            html += '<span class="lsb-vs">VS</span>';
        }
        html += '</div>';
        html += '<div class="lsb-team">';
        html +=   '<span class="lsb-team-name">' + esc(d.team_b) + '</span>';
        html +=   '<span class="lsb-goal">' + (s.score_b || 0) + '</span>';
        html += '</div>';
        html += '</div>';

        if (s.events) {
            html += '<div class="lsb-events">' + esc(s.events) + '</div>';
        }
        html += '</div>';
        return html;
    }

    function buildCricketScore(d) {
        var s = d.score || {};
        var html = '<div class="lsb-cricket">';

        html += '<div class="lsb-cricket-innings">';
        html += '<div class="lsb-cricket-team">';
        html +=   '<span class="lsb-team-name">' + esc(d.team_a) + '</span>';
        html +=   '<span class="lsb-runs">' + (s.score_a_runs || 0) + '/' + (s.score_a_wkts || 0) + '</span>';
        html +=   '<span class="lsb-overs">(' + (s.score_a_overs || 0) + ' ov)</span>';
        html += '</div>';
        html += '<div class="lsb-cricket-divider">vs</div>';
        html += '<div class="lsb-cricket-team">';
        html +=   '<span class="lsb-team-name">' + esc(d.team_b) + '</span>';
        html +=   '<span class="lsb-runs">' + (s.score_b_runs || 0) + '/' + (s.score_b_wkts || 0) + '</span>';
        html +=   '<span class="lsb-overs">(' + (s.score_b_overs || 0) + ' ov)</span>';
        html += '</div>';
        html += '</div>';

        if (s.target) {
            html += '<div class="lsb-target">Target: ' + s.target + '</div>';
        }
        if (s.result_text) {
            html += '<div class="lsb-result">' + esc(s.result_text) + '</div>';
        }
        if (s.batting_team) {
            var batting = s.batting_team == 1 ? d.team_a : d.team_b;
            html += '<div class="lsb-batting">Batting: ' + esc(batting) + ' — Innings ' + (s.innings || 1) + '</div>';
        }
        html += '</div>';
        return html;
    }

    // ── Update panel ──────────────────────────────────────────────────────
    function initUpdatePanel() {
        var $panel = $('.lsb-update-panel');
        if (!$panel.length) return;

        var matchId = $panel.data('match-id');

        $panel.on('click', '.lsb-step-btn', function () {
            var $btn = $(this);
            var $input = $btn.siblings('input[type="number"]').first();
            if (!$input.length) return;

            var direction = parseFloat($btn.data('step')) || 0;
            var step = parseFloat($input.attr('step')) || 1;
            var min = parseFloat($input.attr('min'));
            var max = parseFloat($input.attr('max'));
            var value = parseFloat($input.val());
            var decimals = decimalPlaces(step);

            if (isNaN(value)) value = 0;
            if (isCricketOversInput($input)) {
                value = stepCricketOvers(value, direction);
                decimals = 1;
            } else {
                value = value + (direction * step);
            }

            if (!isNaN(min)) value = Math.max(min, value);
            if (!isNaN(max)) value = Math.min(max, value);

            $input.val(value.toFixed(decimals)).trigger('input').trigger('change');
        });

        $panel.on('click', '.lsb-run-btn', function () {
            var $btn = $(this);
            var target = $btn.closest('.lsb-run-quick').data('target');
            var $input = $panel.find('[name="' + target + '"]');
            var runs = parseInt($btn.data('runs'), 10) || 0;
            var value = parseInt($input.val(), 10) || 0;

            if (!$input.length) return;
            $input.val(value + runs).trigger('input').trigger('change');
        });

        $panel.on('click', '.lsb-update-btn', function () {
            var action = $(this).data('action');
            var data   = { action: action, nonce: lsbConfig.nonce, match_id: matchId };

            $panel.find('.lsb-field').each(function () {
                var name = $(this).attr('name');
                if (name) data[name] = $(this).val();
            });

            $.post(lsbConfig.ajaxUrl, data)
            .done(function (res) {
                var $msg = $panel.find('.lsb-update-msg');
                if (res.success) {
                    $msg.text('✓ Score updated!').css('color', '#00a32a');
                    // Re-render the board immediately with the fresh payload
                    var $b = $('.lsb-board[data-match-id="' + matchId + '"]');
                    if ($b.length) {
                        $b.html(buildBoardHtml(res.data));
                    }
                    // Keep panel fields in sync with what was just saved
                    var s = res.data.score || {};
                    $panel.find('[name="status"]').val(res.data.status);
                    Object.keys(s).forEach(function (k) {
                        var $f = $panel.find('[name="' + k + '"]');
                        if ($f.length) $f.val(s[k]);
                    });
                } else {
                    var errMsg = res.data && res.data.message ? res.data.message : 'Unknown error';
                    $msg.text('Error: ' + errMsg).css('color', '#d63638');
                }
                setTimeout(function () { $msg.text(''); }, 4000);
            })
            .fail(function (xhr) {
                $panel.find('.lsb-update-msg')
                    .text('Request failed (' + xhr.status + '). Check you are still logged in.')
                    .css('color', '#d63638');
            });
        });
    }

    // ── Dashboard – create match ───────────────────────────────────────────
    function initDashboard() {
        var $btn = $('#lsb-create-btn');
        if (!$btn.length) return;

        $btn.on('click', function () {
            var data = {
                action: 'lsb_create_match',
                nonce:  lsbConfig.nonce,
                sport:  $('#lsb-new-sport').val(),
                title:  $('#lsb-new-title').val(),
                team_a: $('#lsb-new-team-a').val(),
                team_b: $('#lsb-new-team-b').val(),
            };

            $.post(lsbConfig.ajaxUrl, data).done(function (res) {
                var $msg = $('.lsb-create-msg');
                if (res.success) {
                    $msg.html('✓ Match created! <a href="' + res.data.url + '" target="_blank">View scoreboard</a>').css('color', '#00a32a');
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    $msg.text('Error: ' + (res.data && res.data.message ? res.data.message : 'Unknown')).css('color', '#d63638');
                }
            });
        });

        $(document).on('click', '.lsb-copy-overlay', function () {
            var url = $(this).data('url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    alert('OBS overlay URL copied!\n\n' + url + '\n\nIn OBS: Add → Browser Source → paste URL\nSet width 1920, height 1080, tick "Shutdown when not visible".');
                });
            } else {
                window.prompt('Copy this URL into OBS Browser Source:', url);
            }
        });

        $(document).on('click', '.lsb-delete-btn', function () {
            if (!confirm('Delete this match?')) return;
            var id = $(this).data('id');
            $.post(lsbConfig.ajaxUrl, { action: 'lsb_delete_match', nonce: lsbConfig.nonce, match_id: id }).done(function () {
                location.reload();
            });
        });
    }

    // ── End Match button ──────────────────────────────────────────────────
    function initEndMatch() {
        $(document).on('click', '.lsb-end-match-btn', function () {
            if (!confirm('End this match? This will mark it as finished and remove it from the live list.')) return;
            var $btn  = $(this);
            var $msg  = $btn.siblings('.lsb-end-msg');
            var id    = $btn.data('match-id');
            $btn.prop('disabled', true).text('Ending…');
            $.post(lsbConfig.ajaxUrl, { action: 'lsb_end_match', nonce: lsbConfig.nonce, match_id: id })
                .done(function (res) {
                    if (res.success) {
                        $btn.closest('.lsb-end-match-row').html(
                            '<hr><p class="lsb-finished-notice">&#10003; Match ended.</p>'
                        );
                        // Refresh the board to show "Finished" badge
                        var $board = $('.lsb-board[data-match-id="' + id + '"]');
                        if ($board.length) fetchAndRender($board, id);
                    } else {
                        $btn.prop('disabled', false).text('End Match');
                        $msg.text(res.data && res.data.message ? res.data.message : 'Error').css('color', '#d63638');
                    }
                });
        });
    }

    // ── Homepage live widget ──────────────────────────────────────────────
    function initLiveWidget() {
        var $widgets = $('.lsb-live-widget');
        if (!$widgets.length) return;

        function fetchWidget($widget) {
            var sport = $widget.data('sport') || '';
            var id    = $widget.find('.lsb-widget-cards').attr('id');
            $.get(lsbConfig.ajaxUrl, { action: 'lsb_get_live_scores', sport: sport, _: Date.now() })
                .done(function (res) {
                    var $cards = $('#' + id);
                    if (!res.success || !res.data.length) {
                        $cards.html('<div class="lsb-no-live">' + (sport ? esc(sport.charAt(0).toUpperCase() + sport.slice(1)) + ' — ' : '') + 'No live matches right now.</div>');
                        return;
                    }
                    var html = '';
                    res.data.forEach(function (d) {
                        html += buildLiveCard(d);
                    });
                    $cards.html(html);
                });
        }

        $widgets.each(function () {
            var $w = $(this);
            fetchWidget($w);
            setInterval(function () { fetchWidget($w); }, lsbConfig.pollInterval);
        });
    }

    function buildLiveCard(d) {
        var sport = d.sport;
        var s     = d.score || {};
        var url   = lsbConfig.siteUrl + '/scoreboard/' + d.slug;
        var isLive = d.status === 'live';

        var badgeClass = 'lsb-wc-badge--' + esc(d.status);
        var badgeText  = isLive ? '● LIVE' : (d.status === 'finished' ? 'Full Time' : 'Upcoming');

        var scoreHtml = '';
        if (sport === 'football') {
            scoreHtml = '<div class="lsb-wc-ft">'
                + '<span class="lsb-wc-team">' + esc(d.team_a) + '</span>'
                + '<span class="lsb-wc-goals">' + (s.score_a || 0) + '</span>'
                + '<span class="lsb-wc-sep">' + (isLive ? (s.minute || 0) + '\'' : ':') + '</span>'
                + '<span class="lsb-wc-goals">' + (s.score_b || 0) + '</span>'
                + '<span class="lsb-wc-team">' + esc(d.team_b) + '</span>'
                + '</div>';
        } else {
            scoreHtml = '<div class="lsb-wc-ck">'
                + '<span class="lsb-wc-team">' + esc(d.team_a) + '</span>'
                + '<span class="lsb-wc-runs">' + (s.score_a_runs || 0) + '/' + (s.score_a_wkts || 0) + '</span>'
                + '<span class="lsb-wc-vs">vs</span>'
                + '<span class="lsb-wc-runs">' + (s.score_b_runs || 0) + '/' + (s.score_b_wkts || 0) + '</span>'
                + '<span class="lsb-wc-team">' + esc(d.team_b) + '</span>'
                + '</div>';
        }

        return '<a class="lsb-wc lsb-wc--' + sport + '" href="' + esc(url) + '">'
            + '<div class="lsb-wc-top">'
            +   '<span class="lsb-wc-title">' + esc(d.title) + '</span>'
            +   '<span class="lsb-wc-badge ' + badgeClass + '">' + badgeText + '</span>'
            + '</div>'
            + scoreHtml
            + '</a>';
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    function esc(str) {
        return $('<div>').text(String(str || '')).html();
    }

    function decimalPlaces(num) {
        var text = String(num);
        return text.indexOf('.') === -1 ? 0 : text.split('.')[1].length;
    }

    function isCricketOversInput($input) {
        return String($input.attr('name') || '').indexOf('_overs') !== -1;
    }

    function stepCricketOvers(value, direction) {
        var overs = Math.floor(value);
        var balls = Math.round((value - overs) * 10) + direction;

        while (balls >= 6) {
            overs += 1;
            balls -= 6;
        }
        while (balls < 0) {
            if (overs <= 0) {
                overs = 0;
                balls = 0;
                break;
            }
            overs -= 1;
            balls += 6;
        }

        return parseFloat(overs + '.' + balls);
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    $(function () {
        initBoards();
        initUpdatePanel();
        initDashboard();
        initEndMatch();
        initLiveWidget();
    });

}(jQuery));
