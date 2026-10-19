// Shared by the staff and super admin concern tables.
// Turns the server's `submitted_ts`, `response_hours` and `remaining_seconds` into
// the live "Expected Response Time" countdown and exact submitted date/time.
//
// remaining_seconds is measured by the server (database clock) at the moment of the fetch;
// the page counts down from there, so a wrong clock on this computer cannot skew it.
// Call ConcernTimer.markFetched() right after (re)loading rows.
(function (global) {
    var fetchedAt = Date.now();
    var pad = function (n) { return String(n).padStart(2, '0'); };

    function remainingNow(c) {
        return c.remaining_seconds - Math.floor((Date.now() - fetchedAt) / 1000);
    }

    function duration(totalSeconds) {
        var s = Math.abs(totalSeconds);
        return Math.floor(s / 3600) + 'h ' + pad(Math.floor((s % 3600) / 60)) + 'm ' + pad(s % 60) + 's';
    }

    function dateTime(ts, withSeconds) {
        var opts = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' };
        if (withSeconds) opts.second = '2-digit';
        return new Date(ts * 1000).toLocaleString('en-US', opts);
    }

    // What to show for a concern's countdown right now
    function state(c) {
        var due = 'Due ' + dateTime(c.submitted_ts + c.response_hours * 3600);
        if (c.remaining_seconds === null || c.remaining_seconds === undefined) {
            return { cls: 'closed', time: 'Closed', sub: 'Archived' };
        }
        var left = remainingNow(c);
        if (left <= 0) return { cls: 'late', time: 'Overdue ' + duration(left), sub: due };
        var pct = left / (c.response_hours * 3600) * 100;
        return { cls: pct <= 25 ? 'warn' : 'ok', time: duration(left), sub: due };
    }

    // HTML for a table cell; tick() later rewrites it in place using data-eta="<id>"
    function cellHtml(c, esc, prefix) {
        var st = state(c);
        return '<td class="eta ' + st.cls + '" data-eta="' + (prefix || '') + c.id + '">' +
            '<span class="eta-time">' + esc(st.time) + '</span>' +
            '<span class="eta-sub">' + esc(st.sub) + '</span></td>';
    }

    // Updates every visible countdown cell (and the open concern card, if any)
    function tick(rows, cardId, cardEls, prefix) {
        rows.forEach(function (c) {
            var cell = document.querySelector('td[data-eta="' + (prefix || '') + c.id + '"]');
            if (!cell) return;
            var st = state(c);
            cell.className = 'eta ' + st.cls;
            cell.querySelector('.eta-time').textContent = st.time;
            cell.querySelector('.eta-sub').textContent = st.sub;
        });
        if (cardId != null && cardEls) {
            var c = rows.find(function (x) { return x.id === cardId; });
            if (c) {
                var st = state(c);
                cardEls.time.textContent = st.time;
                cardEls.time.className = 'detail-value eta-' + st.cls;
                cardEls.sub.textContent = st.sub;
            }
        }
    }

    global.ConcernTimer = {
        markFetched: function () { fetchedAt = Date.now(); },
        state: state, cellHtml: cellHtml, tick: tick, dateTime: dateTime, duration: duration
    };
})(window);
