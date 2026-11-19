{{-- Full-screen photo viewer, Messenger style (same as the app's
     photo_viewer.dart): black backdrop, x + who / when top left, zoom out /
     zoom in top right. Wheel or pinch zooms around the pointer, double-click
     or double-tap zooms in / back out, drag pans while zoomed, drag down
     (not zoomed) closes, Esc closes. Any <img data-zoom data-name data-when>
     opens it. --}}
<dialog class="pv" id="pv" aria-label="Photo">
    <div class="pv-bg"></div>
    <div class="pv-stage"><img class="pv-img" alt=""></div>
    <div class="pv-top">
        <button type="button" class="pv-btn" data-act="close" aria-label="Close"><i class="i" data-i="xmark"></i></button>
        <div class="pv-who"><b></b><span></span></div>
        <button type="button" class="pv-btn" data-act="out" aria-label="Zoom out"><i class="i" data-i="zoom_out"></i></button>
        <button type="button" class="pv-btn" data-act="in" aria-label="Zoom in"><i class="i" data-i="zoom_in"></i></button>
    </div>
</dialog>
<script>
    (function () {
        var pv = document.getElementById('pv'), stage = pv.querySelector('.pv-stage'), img = pv.querySelector('.pv-img'), bg = pv.querySelector('.pv-bg');
        var MAX = 5, s = 1, tx = 0, ty = 0, drag = 0, pts = new Map(), last = null, pinch = null, lastTap = 0, opener = null;

        function bounds() { // how far the zoomed photo may move before its edge leaves the screen edge
            var w = img.clientWidth * s, h = img.clientHeight * s;
            return [Math.max(0, (w - innerWidth) / 2), Math.max(0, (h - innerHeight) / 2)];
        }
        function clamp() { var b = bounds(); tx = Math.max(-b[0], Math.min(b[0], tx)); ty = Math.max(-b[1], Math.min(b[1], ty)); }
        function draw(anim) {
            img.style.transition = anim ? 'transform .28s cubic-bezier(.2,.8,.2,1)' : 'none';
            img.style.transform = 'translate(' + tx + 'px,' + (ty + drag) + 'px) scale(' + s + ')';
            bg.style.opacity = drag ? Math.max(0.2, 1 - Math.abs(drag) / 400) : 1;
            pv.querySelector('[data-act=out]').disabled = s <= 1;
            pv.querySelector('[data-act=in]').disabled = s >= MAX;
            pv.classList.toggle('zoomed', s > 1);
        }
        // Zoom to [to], keeping the screen point (x, y) under the finger / pointer.
        function zoomAt(to, x, y, anim) {
            to = Math.max(1, Math.min(MAX, to));
            var px = x - innerWidth / 2, py = y - innerHeight / 2;
            tx = px - (px - tx) * to / s; ty = py - (py - ty) * to / s; s = to;
            if (s === 1) { tx = 0; ty = 0; }
            clamp(); draw(anim);
        }
        function open(el) {
            opener = el;
            img.src = el.currentSrc || el.src;
            img.alt = el.alt || 'Photo';
            pv.querySelector('.pv-who b').textContent = el.dataset.name || '';
            pv.querySelector('.pv-who span').textContent = el.dataset.when || '';
            s = 1; tx = ty = drag = 0; draw();
            pv.showModal(); // a modal, so it also opens above the map's report sheet
            requestAnimationFrame(function () { pv.classList.add('on'); });
            document.documentElement.style.overflow = 'hidden';
            pv.querySelector('[data-act=close]').focus();
        }
        function close() {
            pv.classList.remove('on');
            document.documentElement.style.overflow = '';
            setTimeout(function () { pv.close(); img.removeAttribute('src'); }, 200);
            if (opener) opener.focus && opener.focus();
        }
        document.addEventListener('click', function (e) {
            var el = e.target.closest('img[data-zoom]');
            if (el) { e.preventDefault(); open(el); }
        });
        document.addEventListener('keydown', function (e) {
            if (!pv.open) return;
            if (e.key === '+' || e.key === '=') zoomAt(s * 1.6, innerWidth / 2, innerHeight / 2, true);
            if (e.key === '-') zoomAt(s / 1.6, innerWidth / 2, innerHeight / 2, true);
        });
        pv.addEventListener('cancel', function (e) { e.preventDefault(); close(); }); // Esc
        pv.querySelector('.pv-top').addEventListener('click', function (e) {
            var b = e.target.closest('[data-act]'); if (!b) return;
            if (b.dataset.act === 'close') close();
            else zoomAt(s * (b.dataset.act === 'in' ? 1.6 : 1 / 1.6), innerWidth / 2, innerHeight / 2, true);
        });
        stage.addEventListener('wheel', function (e) { e.preventDefault(); zoomAt(s * Math.exp(-e.deltaY / 450), e.clientX, e.clientY); }, { passive: false });
        stage.addEventListener('dblclick', function (e) { zoomAt(s > 1 ? 1 : 2.5, e.clientX, e.clientY, true); });

        stage.addEventListener('pointerdown', function (e) {
            stage.setPointerCapture(e.pointerId);
            pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pts.size === 1) {
                last = { x: e.clientX, y: e.clientY, t: performance.now() };
                // double-tap (touch): zoom in at the tap, or back out
                if (e.pointerType !== 'mouse' && performance.now() - lastTap < 300) { zoomAt(s > 1 ? 1 : 2.5, e.clientX, e.clientY, true); lastTap = 0; last = null; }
                else lastTap = performance.now();
            }
            if (pts.size === 2) { var p = Array.from(pts.values()); pinch = { d: Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y), s: s, mx: (p[0].x + p[1].x) / 2, my: (p[0].y + p[1].y) / 2 }; last = null; }
        });
        stage.addEventListener('pointermove', function (e) {
            if (!pts.has(e.pointerId)) return;
            pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pinch && pts.size === 2) {
                var p = Array.from(pts.values()), mx = (p[0].x + p[1].x) / 2, my = (p[0].y + p[1].y) / 2;
                tx += mx - pinch.mx; ty += my - pinch.my; pinch.mx = mx; pinch.my = my;
                zoomAt(pinch.s * Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y) / pinch.d, mx, my);
                return;
            }
            if (!last) return;
            var dx = e.clientX - last.x, dy = e.clientY - last.y;
            last.x = e.clientX; last.y = e.clientY;
            if (s > 1) { tx += dx; ty += dy; clamp(); draw(); } // pan the zoomed photo
            else { drag += dy; draw(); } // not zoomed: drag down (or up) to close
        });
        function up(e) {
            pts.delete(e.pointerId);
            if (pts.size < 2) pinch = null;
            if (pts.size === 0) {
                if (drag) { if (Math.abs(drag) > 120) { close(); } drag = 0; draw(true); }
                last = null;
            }
        }
        stage.addEventListener('pointerup', up);
        stage.addEventListener('pointercancel', up);
        // A click on the empty black area (not the photo) closes, like Messenger.
        stage.addEventListener('click', function (e) { if (e.target === stage && s === 1) close(); });
        addEventListener('resize', function () { if (pv.open) { clamp(); draw(); } });
    })();
</script>
