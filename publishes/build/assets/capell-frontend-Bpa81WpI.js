var e = [`top`, `right`, `bottom`, `left`],
    t = [`start`, `end`],
    n = e.reduce((e, n) => e.concat(n, n + `-` + t[0], n + `-` + t[1]), []),
    r = Math.min,
    i = Math.max,
    a = Math.round,
    o = Math.floor,
    s = (e) => ({ x: e, y: e }),
    c = { left: `right`, right: `left`, bottom: `top`, top: `bottom` },
    l = { start: `end`, end: `start` }
function u(e, t, n) {
    return i(e, r(t, n))
}
function d(e, t) {
    return typeof e == `function` ? e(t) : e
}
function f(e) {
    return e.split(`-`)[0]
}
function p(e) {
    return e.split(`-`)[1]
}
function m(e) {
    return e === `x` ? `y` : `x`
}
function h(e) {
    return e === `y` ? `height` : `width`
}
function g(e) {
    return [`top`, `bottom`].includes(f(e)) ? `y` : `x`
}
function _(e) {
    return m(g(e))
}
function v(e, t, n) {
    n === void 0 && (n = !1)
    let r = p(e),
        i = _(e),
        a = h(i),
        o =
            i === `x`
                ? r === (n ? `end` : `start`)
                    ? `right`
                    : `left`
                : r === `start`
                  ? `bottom`
                  : `top`
    return (t.reference[a] > t.floating[a] && (o = C(o)), [o, C(o)])
}
function y(e) {
    let t = C(e)
    return [b(e), t, b(t)]
}
function b(e) {
    return e.replace(/start|end/g, (e) => l[e])
}
function x(e, t, n) {
    let r = [`left`, `right`],
        i = [`right`, `left`],
        a = [`top`, `bottom`],
        o = [`bottom`, `top`]
    switch (e) {
        case `top`:
        case `bottom`:
            return n ? (t ? i : r) : t ? r : i
        case `left`:
        case `right`:
            return t ? a : o
        default:
            return []
    }
}
function S(e, t, n, r) {
    let i = p(e),
        a = x(f(e), n === `start`, r)
    return (
        i && ((a = a.map((e) => e + `-` + i)), t && (a = a.concat(a.map(b)))),
        a
    )
}
function C(e) {
    return e.replace(/left|right|bottom|top/g, (e) => c[e])
}
function w(e) {
    return { top: 0, right: 0, bottom: 0, left: 0, ...e }
}
function T(e) {
    return typeof e == `number`
        ? { top: e, right: e, bottom: e, left: e }
        : w(e)
}
function E(e) {
    return {
        ...e,
        top: e.y,
        left: e.x,
        right: e.x + e.width,
        bottom: e.y + e.height,
    }
}
function D(e, t, n) {
    let { reference: r, floating: i } = e,
        a = g(t),
        o = _(t),
        s = h(o),
        c = f(t),
        l = a === `y`,
        u = r.x + r.width / 2 - i.width / 2,
        d = r.y + r.height / 2 - i.height / 2,
        m = r[s] / 2 - i[s] / 2,
        v
    switch (c) {
        case `top`:
            v = { x: u, y: r.y - i.height }
            break
        case `bottom`:
            v = { x: u, y: r.y + r.height }
            break
        case `right`:
            v = { x: r.x + r.width, y: d }
            break
        case `left`:
            v = { x: r.x - i.width, y: d }
            break
        default:
            v = { x: r.x, y: r.y }
    }
    switch (p(t)) {
        case `start`:
            v[o] -= m * (n && l ? -1 : 1)
            break
        case `end`:
            v[o] += m * (n && l ? -1 : 1)
            break
    }
    return v
}
var O = async (e, t, n) => {
    let {
            placement: r = `bottom`,
            strategy: i = `absolute`,
            middleware: a = [],
            platform: o,
        } = n,
        s = a.filter(Boolean),
        c = await (o.isRTL == null ? void 0 : o.isRTL(t)),
        l = await o.getElementRects({ reference: e, floating: t, strategy: i }),
        { x: u, y: d } = D(l, r, c),
        f = r,
        p = {},
        m = 0
    for (let n = 0; n < s.length; n++) {
        let { name: a, fn: h } = s[n],
            {
                x: g,
                y: _,
                data: v,
                reset: y,
            } = await h({
                x: u,
                y: d,
                initialPlacement: r,
                placement: f,
                strategy: i,
                middlewareData: p,
                rects: l,
                platform: o,
                elements: { reference: e, floating: t },
            })
        ;((u = g ?? u),
            (d = _ ?? d),
            (p = { ...p, [a]: { ...p[a], ...v } }),
            y &&
                m <= 50 &&
                (m++,
                typeof y == `object` &&
                    (y.placement && (f = y.placement),
                    y.rects &&
                        (l =
                            y.rects === !0
                                ? await o.getElementRects({
                                      reference: e,
                                      floating: t,
                                      strategy: i,
                                  })
                                : y.rects),
                    ({ x: u, y: d } = D(l, f, c))),
                (n = -1)))
    }
    return { x: u, y: d, placement: f, strategy: i, middlewareData: p }
}
async function k(e, t) {
    t === void 0 && (t = {})
    let { x: n, y: r, platform: i, rects: a, elements: o, strategy: s } = e,
        {
            boundary: c = `clippingAncestors`,
            rootBoundary: l = `viewport`,
            elementContext: u = `floating`,
            altBoundary: f = !1,
            padding: p = 0,
        } = d(t, e),
        m = T(p),
        h = o[f ? (u === `floating` ? `reference` : `floating`) : u],
        g = E(
            await i.getClippingRect({
                element:
                    ((await (i.isElement == null ? void 0 : i.isElement(h))) ??
                    !0)
                        ? h
                        : h.contextElement ||
                          (await (i.getDocumentElement == null
                              ? void 0
                              : i.getDocumentElement(o.floating))),
                boundary: c,
                rootBoundary: l,
                strategy: s,
            }),
        ),
        _ = u === `floating` ? { ...a.floating, x: n, y: r } : a.reference,
        v = await (i.getOffsetParent == null
            ? void 0
            : i.getOffsetParent(o.floating)),
        y = ((await (i.isElement == null ? void 0 : i.isElement(v))) &&
            (await (i.getScale == null ? void 0 : i.getScale(v)))) || {
            x: 1,
            y: 1,
        },
        b = E(
            i.convertOffsetParentRelativeRectToViewportRelativeRect
                ? await i.convertOffsetParentRelativeRectToViewportRelativeRect(
                      { elements: o, rect: _, offsetParent: v, strategy: s },
                  )
                : _,
        )
    return {
        top: (g.top - b.top + m.top) / y.y,
        bottom: (b.bottom - g.bottom + m.bottom) / y.y,
        left: (g.left - b.left + m.left) / y.x,
        right: (b.right - g.right + m.right) / y.x,
    }
}
var A = (e) => ({
    name: `arrow`,
    options: e,
    async fn(t) {
        let {
                x: n,
                y: i,
                placement: a,
                rects: o,
                platform: s,
                elements: c,
                middlewareData: l,
            } = t,
            { element: f, padding: m = 0 } = d(e, t) || {}
        if (f == null) return {}
        let g = T(m),
            v = { x: n, y: i },
            y = _(a),
            b = h(y),
            x = await s.getDimensions(f),
            S = y === `y`,
            C = S ? `top` : `left`,
            w = S ? `bottom` : `right`,
            E = S ? `clientHeight` : `clientWidth`,
            D = o.reference[b] + o.reference[y] - v[y] - o.floating[b],
            O = v[y] - o.reference[y],
            k = await (s.getOffsetParent == null
                ? void 0
                : s.getOffsetParent(f)),
            A = k ? k[E] : 0
        ;(!A || !(await (s.isElement == null ? void 0 : s.isElement(k)))) &&
            (A = c.floating[E] || o.floating[b])
        let j = D / 2 - O / 2,
            M = A / 2 - x[b] / 2 - 1,
            N = r(g[C], M),
            ee = r(g[w], M),
            P = N,
            F = A - x[b] - ee,
            I = A / 2 - x[b] / 2 + j,
            L = u(P, I, F),
            te =
                !l.arrow &&
                p(a) != null &&
                I !== L &&
                o.reference[b] / 2 - (I < P ? N : ee) - x[b] / 2 < 0,
            ne = te ? (I < P ? I - P : I - F) : 0
        return {
            [y]: v[y] + ne,
            data: {
                [y]: L,
                centerOffset: I - L - ne,
                ...(te && { alignmentOffset: ne }),
            },
            reset: te,
        }
    },
})
function j(e, t, n) {
    return (
        e
            ? [...n.filter((t) => p(t) === e), ...n.filter((t) => p(t) !== e)]
            : n.filter((e) => f(e) === e)
    ).filter((n) => (e ? p(n) === e || (t ? b(n) !== n : !1) : !0))
}
var M = function (e) {
        return (
            e === void 0 && (e = {}),
            {
                name: `autoPlacement`,
                options: e,
                async fn(t) {
                    let {
                            rects: r,
                            middlewareData: i,
                            placement: a,
                            platform: o,
                            elements: s,
                        } = t,
                        {
                            crossAxis: c = !1,
                            alignment: l,
                            allowedPlacements: u = n,
                            autoAlignment: m = !0,
                            ...h
                        } = d(e, t),
                        g = l !== void 0 || u === n ? j(l || null, m, u) : u,
                        _ = await k(t, h),
                        y = i.autoPlacement?.index || 0,
                        b = g[y]
                    if (b == null) return {}
                    let x = v(
                        b,
                        r,
                        await (o.isRTL == null ? void 0 : o.isRTL(s.floating)),
                    )
                    if (a !== b) return { reset: { placement: g[0] } }
                    let S = [_[f(b)], _[x[0]], _[x[1]]],
                        C = [
                            ...(i.autoPlacement?.overflows || []),
                            { placement: b, overflows: S },
                        ],
                        w = g[y + 1]
                    if (w)
                        return {
                            data: { index: y + 1, overflows: C },
                            reset: { placement: w },
                        }
                    let T = C.map((e) => {
                            let t = p(e.placement)
                            return [
                                e.placement,
                                t && c
                                    ? e.overflows
                                          .slice(0, 2)
                                          .reduce((e, t) => e + t, 0)
                                    : e.overflows[0],
                                e.overflows,
                            ]
                        }).sort((e, t) => e[1] - t[1]),
                        E =
                            T.filter((e) =>
                                e[2]
                                    .slice(0, p(e[0]) ? 2 : 3)
                                    .every((e) => e <= 0),
                            )[0]?.[0] || T[0][0]
                    return E === a
                        ? {}
                        : {
                              data: { index: y + 1, overflows: C },
                              reset: { placement: E },
                          }
                },
            }
        )
    },
    N = function (e) {
        return (
            e === void 0 && (e = {}),
            {
                name: `flip`,
                options: e,
                async fn(t) {
                    var n
                    let {
                            placement: r,
                            middlewareData: i,
                            rects: a,
                            initialPlacement: o,
                            platform: s,
                            elements: c,
                        } = t,
                        {
                            mainAxis: l = !0,
                            crossAxis: u = !0,
                            fallbackPlacements: p,
                            fallbackStrategy: m = `bestFit`,
                            fallbackAxisSideDirection: h = `none`,
                            flipAlignment: g = !0,
                            ..._
                        } = d(e, t)
                    if ((n = i.arrow) != null && n.alignmentOffset) return {}
                    let b = f(r),
                        x = f(o) === o,
                        w = await (s.isRTL == null
                            ? void 0
                            : s.isRTL(c.floating)),
                        T = p || (x || !g ? [C(o)] : y(o))
                    !p && h !== `none` && T.push(...S(o, g, h, w))
                    let E = [o, ...T],
                        D = await k(t, _),
                        O = [],
                        A = i.flip?.overflows || []
                    if ((l && O.push(D[b]), u)) {
                        let e = v(r, a, w)
                        O.push(D[e[0]], D[e[1]])
                    }
                    if (
                        ((A = [...A, { placement: r, overflows: O }]),
                        !O.every((e) => e <= 0))
                    ) {
                        let e = (i.flip?.index || 0) + 1,
                            t = E[e]
                        if (t)
                            return {
                                data: { index: e, overflows: A },
                                reset: { placement: t },
                            }
                        let n = A.filter((e) => e.overflows[0] <= 0).sort(
                            (e, t) => e.overflows[1] - t.overflows[1],
                        )[0]?.placement
                        if (!n)
                            switch (m) {
                                case `bestFit`: {
                                    let e = A.map((e) => [
                                        e.placement,
                                        e.overflows
                                            .filter((e) => e > 0)
                                            .reduce((e, t) => e + t, 0),
                                    ]).sort((e, t) => e[1] - t[1])[0]?.[0]
                                    e && (n = e)
                                    break
                                }
                                case `initialPlacement`:
                                    n = o
                                    break
                            }
                        if (r !== n) return { reset: { placement: n } }
                    }
                    return {}
                },
            }
        )
    }
function ee(e, t) {
    return {
        top: e.top - t.height,
        right: e.right - t.width,
        bottom: e.bottom - t.height,
        left: e.left - t.width,
    }
}
function P(t) {
    return e.some((e) => t[e] >= 0)
}
var F = function (e) {
    return (
        e === void 0 && (e = {}),
        {
            name: `hide`,
            options: e,
            async fn(t) {
                let { rects: n } = t,
                    { strategy: r = `referenceHidden`, ...i } = d(e, t)
                switch (r) {
                    case `referenceHidden`: {
                        let e = ee(
                            await k(t, { ...i, elementContext: `reference` }),
                            n.reference,
                        )
                        return {
                            data: {
                                referenceHiddenOffsets: e,
                                referenceHidden: P(e),
                            },
                        }
                    }
                    case `escaped`: {
                        let e = ee(
                            await k(t, { ...i, altBoundary: !0 }),
                            n.floating,
                        )
                        return { data: { escapedOffsets: e, escaped: P(e) } }
                    }
                    default:
                        return {}
                }
            },
        }
    )
}
function I(e) {
    let t = r(...e.map((e) => e.left)),
        n = r(...e.map((e) => e.top)),
        a = i(...e.map((e) => e.right)),
        o = i(...e.map((e) => e.bottom))
    return { x: t, y: n, width: a - t, height: o - n }
}
function L(e) {
    let t = e.slice().sort((e, t) => e.y - t.y),
        n = [],
        r = null
    for (let e = 0; e < t.length; e++) {
        let i = t[e]
        ;(!r || i.y - r.y > r.height / 2
            ? n.push([i])
            : n[n.length - 1].push(i),
            (r = i))
    }
    return n.map((e) => E(I(e)))
}
var te = function (e) {
    return (
        e === void 0 && (e = {}),
        {
            name: `inline`,
            options: e,
            async fn(t) {
                let {
                        placement: n,
                        elements: a,
                        rects: o,
                        platform: s,
                        strategy: c,
                    } = t,
                    { padding: l = 2, x: u, y: p } = d(e, t),
                    m = Array.from(
                        (await (s.getClientRects == null
                            ? void 0
                            : s.getClientRects(a.reference))) || [],
                    ),
                    h = L(m),
                    _ = E(I(m)),
                    v = T(l)
                function y() {
                    if (
                        h.length === 2 &&
                        h[0].left > h[1].right &&
                        u != null &&
                        p != null
                    )
                        return (
                            h.find(
                                (e) =>
                                    u > e.left - v.left &&
                                    u < e.right + v.right &&
                                    p > e.top - v.top &&
                                    p < e.bottom + v.bottom,
                            ) || _
                        )
                    if (h.length >= 2) {
                        if (g(n) === `y`) {
                            let e = h[0],
                                t = h[h.length - 1],
                                r = f(n) === `top`,
                                i = e.top,
                                a = t.bottom,
                                o = r ? e.left : t.left,
                                s = r ? e.right : t.right
                            return {
                                top: i,
                                bottom: a,
                                left: o,
                                right: s,
                                width: s - o,
                                height: a - i,
                                x: o,
                                y: i,
                            }
                        }
                        let e = f(n) === `left`,
                            t = i(...h.map((e) => e.right)),
                            a = r(...h.map((e) => e.left)),
                            o = h.filter((n) =>
                                e ? n.left === a : n.right === t,
                            ),
                            s = o[0].top,
                            c = o[o.length - 1].bottom,
                            l = a,
                            u = t
                        return {
                            top: s,
                            bottom: c,
                            left: l,
                            right: u,
                            width: u - l,
                            height: c - s,
                            x: l,
                            y: s,
                        }
                    }
                    return _
                }
                let b = await s.getElementRects({
                    reference: { getBoundingClientRect: y },
                    floating: a.floating,
                    strategy: c,
                })
                return o.reference.x !== b.reference.x ||
                    o.reference.y !== b.reference.y ||
                    o.reference.width !== b.reference.width ||
                    o.reference.height !== b.reference.height
                    ? { reset: { rects: b } }
                    : {}
            },
        }
    )
}
async function ne(e, t) {
    let { placement: n, platform: r, elements: i } = e,
        a = await (r.isRTL == null ? void 0 : r.isRTL(i.floating)),
        o = f(n),
        s = p(n),
        c = g(n) === `y`,
        l = [`left`, `top`].includes(o) ? -1 : 1,
        u = a && c ? -1 : 1,
        m = d(t, e),
        {
            mainAxis: h,
            crossAxis: _,
            alignmentAxis: v,
        } = typeof m == `number`
            ? { mainAxis: m, crossAxis: 0, alignmentAxis: null }
            : { mainAxis: 0, crossAxis: 0, alignmentAxis: null, ...m }
    return (
        s && typeof v == `number` && (_ = s === `end` ? v * -1 : v),
        c ? { x: _ * u, y: h * l } : { x: h * l, y: _ * u }
    )
}
var R = function (e) {
        return (
            e === void 0 && (e = 0),
            {
                name: `offset`,
                options: e,
                async fn(t) {
                    var n
                    let { x: r, y: i, placement: a, middlewareData: o } = t,
                        s = await ne(t, e)
                    return a === o.offset?.placement &&
                        (n = o.arrow) != null &&
                        n.alignmentOffset
                        ? {}
                        : {
                              x: r + s.x,
                              y: i + s.y,
                              data: { ...s, placement: a },
                          }
                },
            }
        )
    },
    z = function (e) {
        return (
            e === void 0 && (e = {}),
            {
                name: `shift`,
                options: e,
                async fn(t) {
                    let { x: n, y: r, placement: i } = t,
                        {
                            mainAxis: a = !0,
                            crossAxis: o = !1,
                            limiter: s = {
                                fn: (e) => {
                                    let { x: t, y: n } = e
                                    return { x: t, y: n }
                                },
                            },
                            ...c
                        } = d(e, t),
                        l = { x: n, y: r },
                        p = await k(t, c),
                        h = g(f(i)),
                        _ = m(h),
                        v = l[_],
                        y = l[h]
                    if (a) {
                        let e = _ === `y` ? `top` : `left`,
                            t = _ === `y` ? `bottom` : `right`,
                            n = v + p[e],
                            r = v - p[t]
                        v = u(n, v, r)
                    }
                    if (o) {
                        let e = h === `y` ? `top` : `left`,
                            t = h === `y` ? `bottom` : `right`,
                            n = y + p[e],
                            r = y - p[t]
                        y = u(n, y, r)
                    }
                    let b = s.fn({ ...t, [_]: v, [h]: y })
                    return { ...b, data: { x: b.x - n, y: b.y - r } }
                },
            }
        )
    },
    re = function (e) {
        return (
            e === void 0 && (e = {}),
            {
                name: `size`,
                options: e,
                async fn(t) {
                    let {
                            placement: n,
                            rects: a,
                            platform: o,
                            elements: s,
                        } = t,
                        { apply: c = () => {}, ...l } = d(e, t),
                        u = await k(t, l),
                        m = f(n),
                        h = p(n),
                        _ = g(n) === `y`,
                        { width: v, height: y } = a.floating,
                        b,
                        x
                    m === `top` || m === `bottom`
                        ? ((b = m),
                          (x =
                              h ===
                              ((await (o.isRTL == null
                                  ? void 0
                                  : o.isRTL(s.floating)))
                                  ? `start`
                                  : `end`)
                                  ? `left`
                                  : `right`))
                        : ((x = m), (b = h === `end` ? `top` : `bottom`))
                    let S = y - u[b],
                        C = v - u[x],
                        w = !t.middlewareData.shift,
                        T = S,
                        E = C
                    if (_) {
                        let e = v - u.left - u.right
                        E = h || w ? r(C, e) : e
                    } else {
                        let e = y - u.top - u.bottom
                        T = h || w ? r(S, e) : e
                    }
                    if (w && !h) {
                        let e = i(u.left, 0),
                            t = i(u.right, 0),
                            n = i(u.top, 0),
                            r = i(u.bottom, 0)
                        _
                            ? (E =
                                  v -
                                  2 *
                                      (e !== 0 || t !== 0
                                          ? e + t
                                          : i(u.left, u.right)))
                            : (T =
                                  y -
                                  2 *
                                      (n !== 0 || r !== 0
                                          ? n + r
                                          : i(u.top, u.bottom)))
                    }
                    await c({ ...t, availableWidth: E, availableHeight: T })
                    let D = await o.getDimensions(s.floating)
                    return v !== D.width || y !== D.height
                        ? { reset: { rects: !0 } }
                        : {}
                },
            }
        )
    }
function ie(e) {
    return H(e) ? (e.nodeName || ``).toLowerCase() : `#document`
}
function B(e) {
    var t
    return (
        (e == null || (t = e.ownerDocument) == null ? void 0 : t.defaultView) ||
        window
    )
}
function V(e) {
    return ((H(e) ? e.ownerDocument : e.document) || window.document)
        ?.documentElement
}
function H(e) {
    return e instanceof Node || e instanceof B(e).Node
}
function U(e) {
    return e instanceof Element || e instanceof B(e).Element
}
function W(e) {
    return e instanceof HTMLElement || e instanceof B(e).HTMLElement
}
function G(e) {
    return typeof ShadowRoot > `u`
        ? !1
        : e instanceof ShadowRoot || e instanceof B(e).ShadowRoot
}
function K(e) {
    let { overflow: t, overflowX: n, overflowY: r, display: i } = J(e)
    return (
        /auto|scroll|overlay|hidden|clip/.test(t + r + n) &&
        ![`inline`, `contents`].includes(i)
    )
}
function q(e) {
    return [`table`, `td`, `th`].includes(ie(e))
}
function ae(e) {
    let t = se(),
        n = J(e)
    return (
        n.transform !== `none` ||
        n.perspective !== `none` ||
        (n.containerType ? n.containerType !== `normal` : !1) ||
        (!t && (n.backdropFilter ? n.backdropFilter !== `none` : !1)) ||
        (!t && (n.filter ? n.filter !== `none` : !1)) ||
        [`transform`, `perspective`, `filter`].some((e) =>
            (n.willChange || ``).includes(e),
        ) ||
        [`paint`, `layout`, `strict`, `content`].some((e) =>
            (n.contain || ``).includes(e),
        )
    )
}
function oe(e) {
    let t = Y(e)
    for (; W(t) && !ce(t); )
        if (ae(t)) return t
        else t = Y(t)
    return null
}
function se() {
    return typeof CSS > `u` || !CSS.supports
        ? !1
        : CSS.supports(`-webkit-backdrop-filter`, `none`)
}
function ce(e) {
    return [`html`, `body`, `#document`].includes(ie(e))
}
function J(e) {
    return B(e).getComputedStyle(e)
}
function le(e) {
    return U(e)
        ? { scrollLeft: e.scrollLeft, scrollTop: e.scrollTop }
        : { scrollLeft: e.pageXOffset, scrollTop: e.pageYOffset }
}
function Y(e) {
    if (ie(e) === `html`) return e
    let t = e.assignedSlot || e.parentNode || (G(e) && e.host) || V(e)
    return G(t) ? t.host : t
}
function ue(e) {
    let t = Y(e)
    return ce(t)
        ? e.ownerDocument
            ? e.ownerDocument.body
            : e.body
        : W(t) && K(t)
          ? t
          : ue(t)
}
function X(e, t, n) {
    ;(t === void 0 && (t = []), n === void 0 && (n = !0))
    let r = ue(e),
        i = r === e.ownerDocument?.body,
        a = B(r)
    return i
        ? t.concat(
              a,
              a.visualViewport || [],
              K(r) ? r : [],
              a.frameElement && n ? X(a.frameElement) : [],
          )
        : t.concat(r, X(r, [], n))
}
function de(e) {
    let t = J(e),
        n = parseFloat(t.width) || 0,
        r = parseFloat(t.height) || 0,
        i = W(e),
        o = i ? e.offsetWidth : n,
        s = i ? e.offsetHeight : r,
        c = a(n) !== o || a(r) !== s
    return (c && ((n = o), (r = s)), { width: n, height: r, $: c })
}
function fe(e) {
    return U(e) ? e : e.contextElement
}
function pe(e) {
    let t = fe(e)
    if (!W(t)) return s(1)
    let n = t.getBoundingClientRect(),
        { width: r, height: i, $: o } = de(t),
        c = (o ? a(n.width) : n.width) / r,
        l = (o ? a(n.height) : n.height) / i
    return (
        (!c || !Number.isFinite(c)) && (c = 1),
        (!l || !Number.isFinite(l)) && (l = 1),
        { x: c, y: l }
    )
}
var me = s(0)
function he(e) {
    let t = B(e)
    return !se() || !t.visualViewport
        ? me
        : { x: t.visualViewport.offsetLeft, y: t.visualViewport.offsetTop }
}
function ge(e, t, n) {
    return (t === void 0 && (t = !1), !n || (t && n !== B(e)) ? !1 : t)
}
function Z(e, t, n, r) {
    ;(t === void 0 && (t = !1), n === void 0 && (n = !1))
    let i = e.getBoundingClientRect(),
        a = fe(e),
        o = s(1)
    t && (r ? U(r) && (o = pe(r)) : (o = pe(e)))
    let c = ge(a, n, r) ? he(a) : s(0),
        l = (i.left + c.x) / o.x,
        u = (i.top + c.y) / o.y,
        d = i.width / o.x,
        f = i.height / o.y
    if (a) {
        let e = B(a),
            t = r && U(r) ? B(r) : r,
            n = e,
            i = n.frameElement
        for (; i && r && t !== n; ) {
            let e = pe(i),
                t = i.getBoundingClientRect(),
                r = J(i),
                a = t.left + (i.clientLeft + parseFloat(r.paddingLeft)) * e.x,
                o = t.top + (i.clientTop + parseFloat(r.paddingTop)) * e.y
            ;((l *= e.x),
                (u *= e.y),
                (d *= e.x),
                (f *= e.y),
                (l += a),
                (u += o),
                (n = B(i)),
                (i = n.frameElement))
        }
    }
    return E({ width: d, height: f, x: l, y: u })
}
var _e = [`:popover-open`, `:modal`]
function ve(e) {
    return _e.some((t) => {
        try {
            return e.matches(t)
        } catch {
            return !1
        }
    })
}
function ye(e) {
    let { elements: t, rect: n, offsetParent: r, strategy: i } = e,
        a = i === `fixed`,
        o = V(r),
        c = t ? ve(t.floating) : !1
    if (r === o || (c && a)) return n
    let l = { scrollLeft: 0, scrollTop: 0 },
        u = s(1),
        d = s(0),
        f = W(r)
    if (
        (f || (!f && !a)) &&
        ((ie(r) !== `body` || K(o)) && (l = le(r)), W(r))
    ) {
        let e = Z(r)
        ;((u = pe(r)), (d.x = e.x + r.clientLeft), (d.y = e.y + r.clientTop))
    }
    return {
        width: n.width * u.x,
        height: n.height * u.y,
        x: n.x * u.x - l.scrollLeft * u.x + d.x,
        y: n.y * u.y - l.scrollTop * u.y + d.y,
    }
}
function be(e) {
    return Array.from(e.getClientRects())
}
function xe(e) {
    return Z(V(e)).left + le(e).scrollLeft
}
function Se(e) {
    let t = V(e),
        n = le(e),
        r = e.ownerDocument.body,
        a = i(t.scrollWidth, t.clientWidth, r.scrollWidth, r.clientWidth),
        o = i(t.scrollHeight, t.clientHeight, r.scrollHeight, r.clientHeight),
        s = -n.scrollLeft + xe(e),
        c = -n.scrollTop
    return (
        J(r).direction === `rtl` && (s += i(t.clientWidth, r.clientWidth) - a),
        { width: a, height: o, x: s, y: c }
    )
}
function Ce(e, t) {
    let n = B(e),
        r = V(e),
        i = n.visualViewport,
        a = r.clientWidth,
        o = r.clientHeight,
        s = 0,
        c = 0
    if (i) {
        ;((a = i.width), (o = i.height))
        let e = se()
        ;(!e || (e && t === `fixed`)) && ((s = i.offsetLeft), (c = i.offsetTop))
    }
    return { width: a, height: o, x: s, y: c }
}
function we(e, t) {
    let n = Z(e, !0, t === `fixed`),
        r = n.top + e.clientTop,
        i = n.left + e.clientLeft,
        a = W(e) ? pe(e) : s(1)
    return {
        width: e.clientWidth * a.x,
        height: e.clientHeight * a.y,
        x: i * a.x,
        y: r * a.y,
    }
}
function Te(e, t, n) {
    let r
    if (t === `viewport`) r = Ce(e, n)
    else if (t === `document`) r = Se(V(e))
    else if (U(t)) r = we(t, n)
    else {
        let n = he(e)
        r = { ...t, x: t.x - n.x, y: t.y - n.y }
    }
    return E(r)
}
function Ee(e, t) {
    let n = Y(e)
    return n === t || !U(n) || ce(n)
        ? !1
        : J(n).position === `fixed` || Ee(n, t)
}
function De(e, t) {
    let n = t.get(e)
    if (n) return n
    let r = X(e, [], !1).filter((e) => U(e) && ie(e) !== `body`),
        i = null,
        a = J(e).position === `fixed`,
        o = a ? Y(e) : e
    for (; U(o) && !ce(o); ) {
        let t = J(o),
            n = ae(o)
        ;(!n && t.position === `fixed` && (i = null),
            (
                a
                    ? !n && !i
                    : (!n &&
                          t.position === `static` &&
                          i &&
                          [`absolute`, `fixed`].includes(i.position)) ||
                      (K(o) && !n && Ee(e, o))
            )
                ? (r = r.filter((e) => e !== o))
                : (i = t),
            (o = Y(o)))
    }
    return (t.set(e, r), r)
}
function Oe(e) {
    let { element: t, boundary: n, rootBoundary: a, strategy: o } = e,
        s = [...(n === `clippingAncestors` ? De(t, this._c) : [].concat(n)), a],
        c = s[0],
        l = s.reduce(
            (e, n) => {
                let a = Te(t, n, o)
                return (
                    (e.top = i(a.top, e.top)),
                    (e.right = r(a.right, e.right)),
                    (e.bottom = r(a.bottom, e.bottom)),
                    (e.left = i(a.left, e.left)),
                    e
                )
            },
            Te(t, c, o),
        )
    return {
        width: l.right - l.left,
        height: l.bottom - l.top,
        x: l.left,
        y: l.top,
    }
}
function ke(e) {
    let { width: t, height: n } = de(e)
    return { width: t, height: n }
}
function Ae(e, t, n) {
    let r = W(t),
        i = V(t),
        a = n === `fixed`,
        o = Z(e, !0, a, t),
        c = { scrollLeft: 0, scrollTop: 0 },
        l = s(0)
    if (r || (!r && !a))
        if (((ie(t) !== `body` || K(i)) && (c = le(t)), r)) {
            let e = Z(t, !0, a, t)
            ;((l.x = e.x + t.clientLeft), (l.y = e.y + t.clientTop))
        } else i && (l.x = xe(i))
    return {
        x: o.left + c.scrollLeft - l.x,
        y: o.top + c.scrollTop - l.y,
        width: o.width,
        height: o.height,
    }
}
function je(e, t) {
    return !W(e) || J(e).position === `fixed` ? null : t ? t(e) : e.offsetParent
}
function Me(e, t) {
    let n = B(e)
    if (!W(e) || ve(e)) return n
    let r = je(e, t)
    for (; r && q(r) && J(r).position === `static`; ) r = je(r, t)
    return r &&
        (ie(r) === `html` ||
            (ie(r) === `body` && J(r).position === `static` && !ae(r)))
        ? n
        : r || oe(e) || n
}
var Ne = async function (e) {
    let t = this.getOffsetParent || Me,
        n = this.getDimensions
    return {
        reference: Ae(e.reference, await t(e.floating), e.strategy),
        floating: { x: 0, y: 0, ...(await n(e.floating)) },
    }
}
function Pe(e) {
    return J(e).direction === `rtl`
}
var Fe = {
    convertOffsetParentRelativeRectToViewportRelativeRect: ye,
    getDocumentElement: V,
    getClippingRect: Oe,
    getOffsetParent: Me,
    getElementRects: Ne,
    getClientRects: be,
    getDimensions: ke,
    getScale: pe,
    isElement: U,
    isRTL: Pe,
}
function Ie(e, t) {
    let n = null,
        a,
        s = V(e)
    function c() {
        var e
        ;(clearTimeout(a), (e = n) == null || e.disconnect(), (n = null))
    }
    function l(u, d) {
        ;(u === void 0 && (u = !1), d === void 0 && (d = 1), c())
        let { left: f, top: p, width: m, height: h } = e.getBoundingClientRect()
        if ((u || t(), !m || !h)) return
        let g = o(p),
            _ = o(s.clientWidth - (f + m)),
            v = o(s.clientHeight - (p + h)),
            y = o(f),
            b = {
                rootMargin: -g + `px ` + -_ + `px ` + -v + `px ` + -y + `px`,
                threshold: i(0, r(1, d)) || 1,
            },
            x = !0
        function S(e) {
            let t = e[0].intersectionRatio
            if (t !== d) {
                if (!x) return l()
                t
                    ? l(!1, t)
                    : (a = setTimeout(() => {
                          l(!1, 1e-7)
                      }, 100))
            }
            x = !1
        }
        try {
            n = new IntersectionObserver(S, { ...b, root: s.ownerDocument })
        } catch {
            n = new IntersectionObserver(S, b)
        }
        n.observe(e)
    }
    return (l(!0), c)
}
function Le(e, t, n, r) {
    r === void 0 && (r = {})
    let {
            ancestorScroll: i = !0,
            ancestorResize: a = !0,
            elementResize: o = typeof ResizeObserver == `function`,
            layoutShift: s = typeof IntersectionObserver == `function`,
            animationFrame: c = !1,
        } = r,
        l = fe(e),
        u = i || a ? [...(l ? X(l) : []), ...X(t)] : []
    u.forEach((e) => {
        ;(i && e.addEventListener(`scroll`, n, { passive: !0 }),
            a && e.addEventListener(`resize`, n))
    })
    let d = l && s ? Ie(l, n) : null,
        f = -1,
        p = null
    o &&
        ((p = new ResizeObserver((e) => {
            let [r] = e
            ;(r &&
                r.target === l &&
                p &&
                (p.unobserve(t),
                cancelAnimationFrame(f),
                (f = requestAnimationFrame(() => {
                    var e
                    ;(e = p) == null || e.observe(t)
                }))),
                n())
        })),
        l && !c && p.observe(l),
        p.observe(t))
    let m,
        h = c ? Z(e) : null
    c && g()
    function g() {
        let t = Z(e)
        ;(h &&
            (t.x !== h.x ||
                t.y !== h.y ||
                t.width !== h.width ||
                t.height !== h.height) &&
            n(),
            (h = t),
            (m = requestAnimationFrame(g)))
    }
    return (
        n(),
        () => {
            var e
            ;(u.forEach((e) => {
                ;(i && e.removeEventListener(`scroll`, n),
                    a && e.removeEventListener(`resize`, n))
            }),
                d?.(),
                (e = p) == null || e.disconnect(),
                (p = null),
                c && cancelAnimationFrame(m))
        }
    )
}
var Re = M,
    ze = z,
    Be = N,
    Ve = re,
    He = F,
    Ue = A,
    We = te,
    Ge = (e, t, n) => {
        let r = new Map(),
            i = { platform: Fe, ...n },
            a = { ...i.platform, _c: r }
        return O(e, t, { ...i, platform: a })
    },
    Ke = (e) => {
        let t = { placement: `bottom`, strategy: `absolute`, middleware: [] },
            n = Object.keys(e),
            r = (t) => e[t]
        return (
            n.includes(`offset`) && t.middleware.push(R(r(`offset`))),
            n.includes(`teleport`) && (t.strategy = `fixed`),
            n.includes(`placement`) && (t.placement = r(`placement`)),
            n.some((e) => /^auto-?placement$/i.test(e)) &&
                !n.includes(`flip`) &&
                t.middleware.push(Re(r(`autoPlacement`))),
            n.includes(`flip`) && t.middleware.push(Be(r(`flip`))),
            n.includes(`shift`) && t.middleware.push(ze(r(`shift`))),
            n.includes(`inline`) && t.middleware.push(We(r(`inline`))),
            n.includes(`arrow`) && t.middleware.push(Ue(r(`arrow`))),
            n.includes(`hide`) && t.middleware.push(He(r(`hide`))),
            n.includes(`size`) && t.middleware.push(Ve(r(`size`))),
            t
        )
    },
    qe = (e, t) => {
        let n = {
                component: { trap: !1 },
                float: {
                    placement: `bottom`,
                    strategy: `absolute`,
                    middleware: [],
                },
            },
            r = (t) => e[e.indexOf(t) + 1]
        if (
            (e.includes(`trap`) && (n.component.trap = !0),
            e.includes(`teleport`) && (n.float.strategy = `fixed`),
            e.includes(`offset`) && n.float.middleware.push(R(t.offset || 10)),
            e.includes(`placement`) && (n.float.placement = r(`placement`)),
            e.some((e) => /^auto-?placement$/i.test(e)) &&
                !e.includes(`flip`) &&
                n.float.middleware.push(Re(t.autoPlacement)),
            e.includes(`flip`) && n.float.middleware.push(Be(t.flip)),
            e.includes(`shift`) && n.float.middleware.push(ze(t.shift)),
            e.includes(`inline`) && n.float.middleware.push(We(t.inline)),
            e.includes(`arrow`) && n.float.middleware.push(Ue(t.arrow)),
            e.includes(`hide`) && n.float.middleware.push(He(t.hide)),
            e.includes(`size`))
        ) {
            let e = t.size?.availableWidth ?? null,
                r = t.size?.availableHeight ?? null
            ;(e && delete t.size.availableWidth,
                r && delete t.size.availableHeight,
                n.float.middleware.push(
                    Ve({
                        ...t.size,
                        apply({
                            availableWidth: t,
                            availableHeight: n,
                            elements: i,
                        }) {
                            Object.assign(i.floating.style, {
                                maxWidth: `${e ?? t}px`,
                                maxHeight: `${r ?? n}px`,
                            })
                        },
                    }),
                ))
        }
        return n
    },
    Je = (e) => {
        var t =
                `0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz`.split(
                    ``,
                ),
            n = ``
        e ||= Math.floor(Math.random() * t.length)
        for (var r = 0; r < e; r++) n += t[Math.floor(Math.random() * t.length)]
        return n
    }
function Ye(e, t = () => {}) {
    let n = !1
    return function () {
        n ? t.apply(this, arguments) : ((n = !0), e.apply(this, arguments))
    }
}
function Xe(e) {
    let t = { dismissable: !0, trap: !1 }
    function n(e, t = null) {
        if (e) {
            if (
                (e.hasAttribute(`aria-expanded`) ||
                    e.setAttribute(`aria-expanded`, !1),
                t.hasAttribute(`id`))
            )
                e.setAttribute(`aria-controls`, t.getAttribute(`id`))
            else {
                let n = `panel-${Je(8)}`
                ;(e.setAttribute(`aria-controls`, n), t.setAttribute(`id`, n))
            }
            ;(t.setAttribute(`aria-modal`, !0),
                t.setAttribute(`role`, `dialog`))
        }
    }
    ;(e.magic(`float`, (e) => (r = {}, i = {}) => {
        let a = { ...t, ...i },
            o = Object.keys(r).length > 0 ? Ke(r) : { middleware: [Re()] },
            s = e,
            c = e.parentElement.closest(`[x-data]`),
            l = c.querySelector(`[x-ref="panel"]`)
        n(s, l)
        function u() {
            return l.style.display == `widget`
        }
        function d() {
            ;((l.style.display = `none`),
                s.setAttribute(`aria-expanded`, `false`),
                a.trap && l.setAttribute(`x-trap`, `false`),
                Le(e, l, m))
        }
        function f() {
            ;((l.style.display = `widget`),
                s.setAttribute(`aria-expanded`, `true`),
                a.trap && l.setAttribute(`x-trap`, `true`),
                m())
        }
        function p() {
            u() ? d() : f()
        }
        async function m() {
            return await Ge(e, l, o).then(
                ({ middlewareData: e, placement: t, x: n, y: r }) => {
                    if (e.arrow) {
                        let n = e.arrow?.x,
                            r = e.arrow?.y,
                            i = o.middleware.filter((e) => e.name == `arrow`)[0]
                                .options.element,
                            a = {
                                top: `bottom`,
                                right: `left`,
                                bottom: `top`,
                                left: `right`,
                            }[t.split(`-`)[0]]
                        Object.assign(i.style, {
                            left: n == null ? `` : `${n}px`,
                            top: r == null ? `` : `${r}px`,
                            right: ``,
                            bottom: ``,
                            [a]: `-4px`,
                        })
                    }
                    if (e.hide) {
                        let { referenceHidden: t } = e.hide
                        Object.assign(l.style, {
                            visibility: t ? `hidden` : `visible`,
                        })
                    }
                    Object.assign(l.style, { left: `${n}px`, top: `${r}px` })
                },
            )
        }
        ;(a.dismissable &&
            (window.addEventListener(`click`, (e) => {
                !c.contains(e.target) && u() && p()
            }),
            window.addEventListener(
                `keydown`,
                (e) => {
                    e.key === `Escape` && u() && p()
                },
                !0,
            )),
            p())
    }),
        e.directive(
            `float`,
            (
                e,
                { modifiers: t, expression: r },
                { evaluate: i, effect: a },
            ) => {
                let o = r ? i(r) : {},
                    s = t.length > 0 ? qe(t, o) : {},
                    c = null
                s.float.strategy == `fixed` && (e.style.position = `fixed`)
                let l = (t) =>
                        e.parentElement &&
                        !e.parentElement.closest(`[x-data]`).contains(t.target)
                            ? e.close()
                            : null,
                    u = (t) => (t.key === `Escape` ? e.close() : null),
                    d = e.getAttribute(`x-ref`),
                    f = e.parentElement.closest(`[x-data]`),
                    p = f.querySelectorAll(`[\\@click^="$refs.${d}"]`),
                    m = f.querySelectorAll(`[x-on\\:click^="$refs.${d}"]`)
                ;(e.style.setProperty(`display`, `none`),
                    n([...p, ...m][0], e),
                    (e._x_isShown = !1),
                    (e.trigger = null),
                    (e._x_doHide ||= () => {
                        e.style.setProperty(
                            `display`,
                            `none`,
                            t.includes(`important`) ? `important` : void 0,
                        )
                    }),
                    (e._x_doShow ||= () => {
                        e.style.setProperty(
                            `display`,
                            `widget`,
                            t.includes(`important`) ? `important` : void 0,
                        )
                    }))
                let h = () => {
                        ;(e._x_doHide(), (e._x_isShown = !1))
                    },
                    g = () => {
                        ;(e._x_doShow(), (e._x_isShown = !0))
                    },
                    _ = () => setTimeout(g),
                    v = Ye(
                        (e) => (e ? g() : h()),
                        (t) => {
                            typeof e._x_toggleAndCascadeWithTransitions ==
                            `function`
                                ? e._x_toggleAndCascadeWithTransitions(
                                      e,
                                      t,
                                      g,
                                      h,
                                  )
                                : t
                                  ? _()
                                  : h()
                        },
                    ),
                    y,
                    b = !0
                ;(a(() =>
                    i((e) => {
                        ;(!b && e === y) ||
                            (t.includes(`immediate`) && (e ? _() : h()),
                            v(e),
                            (y = e),
                            (b = !1))
                    }),
                ),
                    (e.open = async function (t) {
                        ;((e.trigger = t.currentTarget ? t.currentTarget : t),
                            v(!0),
                            e.trigger.setAttribute(`aria-expanded`, `true`),
                            s.component.trap &&
                                e.setAttribute(`x-trap`, `true`),
                            (c = Le(e.trigger, e, () => {
                                Ge(e.trigger, e, s.float).then(
                                    ({
                                        middlewareData: t,
                                        placement: n,
                                        x: r,
                                        y: i,
                                    }) => {
                                        if (t.arrow) {
                                            let e = t.arrow?.x,
                                                r = t.arrow?.y,
                                                i = s.float.middleware.filter(
                                                    (e) => e.name == `arrow`,
                                                )[0].options.element,
                                                a = {
                                                    top: `bottom`,
                                                    right: `left`,
                                                    bottom: `top`,
                                                    left: `right`,
                                                }[n.split(`-`)[0]]
                                            Object.assign(i.style, {
                                                left: e == null ? `` : `${e}px`,
                                                top: r == null ? `` : `${r}px`,
                                                right: ``,
                                                bottom: ``,
                                                [a]: `-4px`,
                                            })
                                        }
                                        if (t.hide) {
                                            let { referenceHidden: n } = t.hide
                                            Object.assign(e.style, {
                                                visibility: n
                                                    ? `hidden`
                                                    : `visible`,
                                            })
                                        }
                                        Object.assign(e.style, {
                                            left: `${r}px`,
                                            top: `${i}px`,
                                        })
                                    },
                                )
                            })),
                            window.addEventListener(`click`, l),
                            window.addEventListener(`keydown`, u, !0))
                    }),
                    (e.close = function () {
                        if (!e._x_isShown) return !1
                        ;(v(!1),
                            e.trigger.setAttribute(`aria-expanded`, `false`),
                            s.component.trap &&
                                e.setAttribute(`x-trap`, `false`),
                            c(),
                            window.removeEventListener(`click`, l),
                            window.removeEventListener(`keydown`, u, !1))
                    }),
                    (e.toggle = function (t) {
                        e._x_isShown ? e.close() : e.open(t)
                    }))
            },
        ))
}
var Ze = Xe,
    Qe = Object.create,
    $e = Object.defineProperty,
    et = Object.getPrototypeOf,
    tt = Object.prototype.hasOwnProperty,
    nt = Object.getOwnPropertyNames,
    rt = Object.getOwnPropertyDescriptor,
    it = (e) => $e(e, `__esModule`, { value: !0 }),
    at = (e, t) => () => (
        t || ((t = { exports: {} }), e(t.exports, t)),
        t.exports
    ),
    ot = (e, t, n) => {
        if ((t && typeof t == `object`) || typeof t == `function`)
            for (let r of nt(t))
                !tt.call(e, r) &&
                    r !== `default` &&
                    $e(e, r, {
                        get: () => t[r],
                        enumerable: !(n = rt(t, r)) || n.enumerable,
                    })
        return e
    },
    st = (e) =>
        ot(
            it(
                $e(
                    e == null ? {} : Qe(et(e)),
                    `default`,
                    e && e.__esModule && `default` in e
                        ? { get: () => e.default, enumerable: !0 }
                        : { value: e, enumerable: !0 },
                ),
            ),
            e,
        ),
    ct = at((e) => {
        Object.defineProperty(e, `__esModule`, { value: !0 })
        function t(e) {
            var t = e.getBoundingClientRect()
            return {
                width: t.width,
                height: t.height,
                top: t.top,
                right: t.right,
                bottom: t.bottom,
                left: t.left,
                x: t.left,
                y: t.top,
            }
        }
        function n(e) {
            if (e == null) return window
            if (e.toString() !== `[object Window]`) {
                var t = e.ownerDocument
                return (t && t.defaultView) || window
            }
            return e
        }
        function r(e) {
            var t = n(e)
            return { scrollLeft: t.pageXOffset, scrollTop: t.pageYOffset }
        }
        function i(e) {
            return e instanceof n(e).Element || e instanceof Element
        }
        function a(e) {
            return e instanceof n(e).HTMLElement || e instanceof HTMLElement
        }
        function o(e) {
            return typeof ShadowRoot > `u`
                ? !1
                : e instanceof n(e).ShadowRoot || e instanceof ShadowRoot
        }
        function s(e) {
            return { scrollLeft: e.scrollLeft, scrollTop: e.scrollTop }
        }
        function c(e) {
            return e === n(e) || !a(e) ? r(e) : s(e)
        }
        function l(e) {
            return e ? (e.nodeName || ``).toLowerCase() : null
        }
        function u(e) {
            return ((i(e) ? e.ownerDocument : e.document) || window.document)
                .documentElement
        }
        function d(e) {
            return t(u(e)).left + r(e).scrollLeft
        }
        function f(e) {
            return n(e).getComputedStyle(e)
        }
        function p(e) {
            var t = f(e),
                n = t.overflow,
                r = t.overflowX,
                i = t.overflowY
            return /auto|scroll|overlay|hidden/.test(n + i + r)
        }
        function m(e, n, r) {
            r === void 0 && (r = !1)
            var i = u(n),
                o = t(e),
                s = a(n),
                f = { scrollLeft: 0, scrollTop: 0 },
                m = { x: 0, y: 0 }
            return (
                (s || (!s && !r)) &&
                    ((l(n) !== `body` || p(i)) && (f = c(n)),
                    a(n)
                        ? ((m = t(n)),
                          (m.x += n.clientLeft),
                          (m.y += n.clientTop))
                        : i && (m.x = d(i))),
                {
                    x: o.left + f.scrollLeft - m.x,
                    y: o.top + f.scrollTop - m.y,
                    width: o.width,
                    height: o.height,
                }
            )
        }
        function h(e) {
            var n = t(e),
                r = e.offsetWidth,
                i = e.offsetHeight
            return (
                Math.abs(n.width - r) <= 1 && (r = n.width),
                Math.abs(n.height - i) <= 1 && (i = n.height),
                { x: e.offsetLeft, y: e.offsetTop, width: r, height: i }
            )
        }
        function g(e) {
            return l(e) === `html`
                ? e
                : e.assignedSlot ||
                      e.parentNode ||
                      (o(e) ? e.host : null) ||
                      u(e)
        }
        function _(e) {
            return [`html`, `body`, `#document`].indexOf(l(e)) >= 0
                ? e.ownerDocument.body
                : a(e) && p(e)
                  ? e
                  : _(g(e))
        }
        function v(e, t) {
            t === void 0 && (t = [])
            var r = _(e),
                i = r === e.ownerDocument?.body,
                a = n(r),
                o = i ? [a].concat(a.visualViewport || [], p(r) ? r : []) : r,
                s = t.concat(o)
            return i ? s : s.concat(v(g(o)))
        }
        function y(e) {
            return [`table`, `td`, `th`].indexOf(l(e)) >= 0
        }
        function b(e) {
            return !a(e) || f(e).position === `fixed` ? null : e.offsetParent
        }
        function x(e) {
            var t = navigator.userAgent.toLowerCase().indexOf(`firefox`) !== -1
            if (
                navigator.userAgent.indexOf(`Trident`) !== -1 &&
                a(e) &&
                f(e).position === `fixed`
            )
                return null
            for (var n = g(e); a(n) && [`html`, `body`].indexOf(l(n)) < 0; ) {
                var r = f(n)
                if (
                    r.transform !== `none` ||
                    r.perspective !== `none` ||
                    r.contain === `paint` ||
                    [`transform`, `perspective`].indexOf(r.willChange) !== -1 ||
                    (t && r.willChange === `filter`) ||
                    (t && r.filter && r.filter !== `none`)
                )
                    return n
                n = n.parentNode
            }
            return null
        }
        function S(e) {
            for (
                var t = n(e), r = b(e);
                r && y(r) && f(r).position === `static`;
            )
                r = b(r)
            return r &&
                (l(r) === `html` ||
                    (l(r) === `body` && f(r).position === `static`))
                ? t
                : r || x(e) || t
        }
        var C = `top`,
            w = `bottom`,
            T = `right`,
            E = `left`,
            D = `auto`,
            O = [C, w, T, E],
            k = `start`,
            A = `end`,
            j = `clippingParents`,
            M = `viewport`,
            N = `popper`,
            ee = `reference`,
            P = O.reduce(function (e, t) {
                return e.concat([t + `-` + k, t + `-` + A])
            }, []),
            F = [].concat(O, [D]).reduce(function (e, t) {
                return e.concat([t, t + `-` + k, t + `-` + A])
            }, []),
            I = [
                `beforeRead`,
                `read`,
                `afterRead`,
                `beforeMain`,
                `main`,
                `afterMain`,
                `beforeWrite`,
                `write`,
                `afterWrite`,
            ]
        function L(e) {
            var t = new Map(),
                n = new Set(),
                r = []
            e.forEach(function (e) {
                t.set(e.name, e)
            })
            function i(e) {
                ;(n.add(e.name),
                    []
                        .concat(e.requires || [], e.requiresIfExists || [])
                        .forEach(function (e) {
                            if (!n.has(e)) {
                                var r = t.get(e)
                                r && i(r)
                            }
                        }),
                    r.push(e))
            }
            return (
                e.forEach(function (e) {
                    n.has(e.name) || i(e)
                }),
                r
            )
        }
        function te(e) {
            var t = L(e)
            return I.reduce(function (e, n) {
                return e.concat(
                    t.filter(function (e) {
                        return e.phase === n
                    }),
                )
            }, [])
        }
        function ne(e) {
            var t
            return function () {
                return (
                    (t ||= new Promise(function (n) {
                        Promise.resolve().then(function () {
                            ;((t = void 0), n(e()))
                        })
                    })),
                    t
                )
            }
        }
        function R(e) {
            var t = [...arguments].slice(1)
            return [].concat(t).reduce(function (e, t) {
                return e.replace(/%s/, t)
            }, e)
        }
        var z = `Popper: modifier "%s" provided an invalid %s property, expected %s but got %s`,
            re = `Popper: modifier "%s" requires "%s", but "%s" modifier is not available`,
            ie = [
                `name`,
                `enabled`,
                `phase`,
                `fn`,
                `effect`,
                `requires`,
                `options`,
            ]
        function B(e) {
            e.forEach(function (t) {
                Object.keys(t).forEach(function (n) {
                    switch (n) {
                        case `name`:
                            typeof t.name != `string` &&
                                console.error(
                                    R(
                                        z,
                                        String(t.name),
                                        `"name"`,
                                        `"string"`,
                                        `"` + String(t.name) + `"`,
                                    ),
                                )
                            break
                        case `enabled`:
                            typeof t.enabled != `boolean` &&
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"enabled"`,
                                        `"boolean"`,
                                        `"` + String(t.enabled) + `"`,
                                    ),
                                )
                        case `phase`:
                            I.indexOf(t.phase) < 0 &&
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"phase"`,
                                        `either ` + I.join(`, `),
                                        `"` + String(t.phase) + `"`,
                                    ),
                                )
                            break
                        case `fn`:
                            typeof t.fn != `function` &&
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"fn"`,
                                        `"function"`,
                                        `"` + String(t.fn) + `"`,
                                    ),
                                )
                            break
                        case `effect`:
                            typeof t.effect != `function` &&
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"effect"`,
                                        `"function"`,
                                        `"` + String(t.fn) + `"`,
                                    ),
                                )
                            break
                        case `requires`:
                            Array.isArray(t.requires) ||
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"requires"`,
                                        `"array"`,
                                        `"` + String(t.requires) + `"`,
                                    ),
                                )
                            break
                        case `requiresIfExists`:
                            Array.isArray(t.requiresIfExists) ||
                                console.error(
                                    R(
                                        z,
                                        t.name,
                                        `"requiresIfExists"`,
                                        `"array"`,
                                        `"` + String(t.requiresIfExists) + `"`,
                                    ),
                                )
                            break
                        case `options`:
                        case `data`:
                            break
                        default:
                            console.error(
                                `PopperJS: an invalid property has been provided to the "` +
                                    t.name +
                                    `" modifier, valid properties are ` +
                                    ie
                                        .map(function (e) {
                                            return `"` + e + `"`
                                        })
                                        .join(`, `) +
                                    `; but "` +
                                    n +
                                    `" was provided.`,
                            )
                    }
                    t.requires &&
                        t.requires.forEach(function (n) {
                            e.find(function (e) {
                                return e.name === n
                            }) ?? console.error(R(re, String(t.name), n, n))
                        })
                })
            })
        }
        function V(e, t) {
            var n = new Set()
            return e.filter(function (e) {
                var r = t(e)
                if (!n.has(r)) return (n.add(r), !0)
            })
        }
        function H(e) {
            return e.split(`-`)[0]
        }
        function U(e) {
            var t = e.reduce(function (e, t) {
                var n = e[t.name]
                return (
                    (e[t.name] = n
                        ? Object.assign({}, n, t, {
                              options: Object.assign({}, n.options, t.options),
                              data: Object.assign({}, n.data, t.data),
                          })
                        : t),
                    e
                )
            }, {})
            return Object.keys(t).map(function (e) {
                return t[e]
            })
        }
        function W(e) {
            var t = n(e),
                r = u(e),
                i = t.visualViewport,
                a = r.clientWidth,
                o = r.clientHeight,
                s = 0,
                c = 0
            return (
                i &&
                    ((a = i.width),
                    (o = i.height),
                    /^((?!chrome|android).)*safari/i.test(
                        navigator.userAgent,
                    ) || ((s = i.offsetLeft), (c = i.offsetTop))),
                { width: a, height: o, x: s + d(e), y: c }
            )
        }
        var G = Math.max,
            K = Math.min,
            q = Math.round
        function ae(e) {
            var t = u(e),
                n = r(e),
                i = e.ownerDocument?.body,
                a = G(
                    t.scrollWidth,
                    t.clientWidth,
                    i ? i.scrollWidth : 0,
                    i ? i.clientWidth : 0,
                ),
                o = G(
                    t.scrollHeight,
                    t.clientHeight,
                    i ? i.scrollHeight : 0,
                    i ? i.clientHeight : 0,
                ),
                s = -n.scrollLeft + d(e),
                c = -n.scrollTop
            return (
                f(i || t).direction === `rtl` &&
                    (s += G(t.clientWidth, i ? i.clientWidth : 0) - a),
                { width: a, height: o, x: s, y: c }
            )
        }
        function oe(e, t) {
            var n = t.getRootNode && t.getRootNode()
            if (e.contains(t)) return !0
            if (n && o(n)) {
                var r = t
                do {
                    if (r && e.isSameNode(r)) return !0
                    r = r.parentNode || r.host
                } while (r)
            }
            return !1
        }
        function se(e) {
            return Object.assign({}, e, {
                left: e.x,
                top: e.y,
                right: e.x + e.width,
                bottom: e.y + e.height,
            })
        }
        function ce(e) {
            var n = t(e)
            return (
                (n.top += e.clientTop),
                (n.left += e.clientLeft),
                (n.bottom = n.top + e.clientHeight),
                (n.right = n.left + e.clientWidth),
                (n.width = e.clientWidth),
                (n.height = e.clientHeight),
                (n.x = n.left),
                (n.y = n.top),
                n
            )
        }
        function J(e, t) {
            return t === M ? se(W(e)) : a(t) ? ce(t) : se(ae(u(e)))
        }
        function le(e) {
            var t = v(g(e)),
                n =
                    [`absolute`, `fixed`].indexOf(f(e).position) >= 0 && a(e)
                        ? S(e)
                        : e
            return i(n)
                ? t.filter(function (e) {
                      return i(e) && oe(e, n) && l(e) !== `body`
                  })
                : []
        }
        function Y(e, t, n) {
            var r = t === `clippingParents` ? le(e) : [].concat(t),
                i = [].concat(r, [n]),
                a = i[0],
                o = i.reduce(
                    function (t, n) {
                        var r = J(e, n)
                        return (
                            (t.top = G(r.top, t.top)),
                            (t.right = K(r.right, t.right)),
                            (t.bottom = K(r.bottom, t.bottom)),
                            (t.left = G(r.left, t.left)),
                            t
                        )
                    },
                    J(e, a),
                )
            return (
                (o.width = o.right - o.left),
                (o.height = o.bottom - o.top),
                (o.x = o.left),
                (o.y = o.top),
                o
            )
        }
        function ue(e) {
            return e.split(`-`)[1]
        }
        function X(e) {
            return [`top`, `bottom`].indexOf(e) >= 0 ? `x` : `y`
        }
        function de(e) {
            var t = e.reference,
                n = e.element,
                r = e.placement,
                i = r ? H(r) : null,
                a = r ? ue(r) : null,
                o = t.x + t.width / 2 - n.width / 2,
                s = t.y + t.height / 2 - n.height / 2,
                c
            switch (i) {
                case C:
                    c = { x: o, y: t.y - n.height }
                    break
                case w:
                    c = { x: o, y: t.y + t.height }
                    break
                case T:
                    c = { x: t.x + t.width, y: s }
                    break
                case E:
                    c = { x: t.x - n.width, y: s }
                    break
                default:
                    c = { x: t.x, y: t.y }
            }
            var l = i ? X(i) : null
            if (l != null) {
                var u = l === `y` ? `height` : `width`
                switch (a) {
                    case k:
                        c[l] = c[l] - (t[u] / 2 - n[u] / 2)
                        break
                    case A:
                        c[l] = c[l] + (t[u] / 2 - n[u] / 2)
                        break
                }
            }
            return c
        }
        function fe() {
            return { top: 0, right: 0, bottom: 0, left: 0 }
        }
        function pe(e) {
            return Object.assign({}, fe(), e)
        }
        function me(e, t) {
            return t.reduce(function (t, n) {
                return ((t[n] = e), t)
            }, {})
        }
        function he(e, n) {
            n === void 0 && (n = {})
            var r = n,
                a = r.placement,
                o = a === void 0 ? e.placement : a,
                s = r.boundary,
                c = s === void 0 ? j : s,
                l = r.rootBoundary,
                d = l === void 0 ? M : l,
                f = r.elementContext,
                p = f === void 0 ? N : f,
                m = r.altBoundary,
                h = m === void 0 ? !1 : m,
                g = r.padding,
                _ = g === void 0 ? 0 : g,
                v = pe(typeof _ == `number` ? me(_, O) : _),
                y = p === N ? ee : N,
                b = e.elements.reference,
                x = e.rects.popper,
                S = e.elements[h ? y : p],
                E = Y(
                    i(S) ? S : S.contextElement || u(e.elements.popper),
                    c,
                    d,
                ),
                D = t(b),
                k = de({
                    reference: D,
                    element: x,
                    strategy: `absolute`,
                    placement: o,
                }),
                A = se(Object.assign({}, x, k)),
                P = p === N ? A : D,
                F = {
                    top: E.top - P.top + v.top,
                    bottom: P.bottom - E.bottom + v.bottom,
                    left: E.left - P.left + v.left,
                    right: P.right - E.right + v.right,
                },
                I = e.modifiersData.offset
            if (p === N && I) {
                var L = I[o]
                Object.keys(F).forEach(function (e) {
                    var t = [T, w].indexOf(e) >= 0 ? 1 : -1,
                        n = [C, w].indexOf(e) >= 0 ? `y` : `x`
                    F[e] += L[n] * t
                })
            }
            return F
        }
        var ge = `Popper: Invalid reference or popper argument provided. They must be either a DOM element or virtual element.`,
            Z = `Popper: An infinite loop in the modifiers cycle has been detected! The cycle has been interrupted to prevent a browser crash.`,
            _e = { placement: `bottom`, modifiers: [], strategy: `absolute` }
        function ve() {
            return ![...arguments].some(function (e) {
                return !(e && typeof e.getBoundingClientRect == `function`)
            })
        }
        function ye(e) {
            e === void 0 && (e = {})
            var t = e,
                n = t.defaultModifiers,
                r = n === void 0 ? [] : n,
                a = t.defaultOptions,
                o = a === void 0 ? _e : a
            return function (e, t, n) {
                n === void 0 && (n = o)
                var a = {
                        placement: `bottom`,
                        orderedModifiers: [],
                        options: Object.assign({}, _e, o),
                        modifiersData: {},
                        elements: { reference: e, popper: t },
                        attributes: {},
                        styles: {},
                    },
                    s = [],
                    c = !1,
                    l = {
                        state: a,
                        setOptions: function (n) {
                            ;(d(),
                                (a.options = Object.assign(
                                    {},
                                    o,
                                    a.options,
                                    n,
                                )),
                                (a.scrollParents = {
                                    reference: i(e)
                                        ? v(e)
                                        : e.contextElement
                                          ? v(e.contextElement)
                                          : [],
                                    popper: v(t),
                                }))
                            var s = te(U([].concat(r, a.options.modifiers)))
                            ;((a.orderedModifiers = s.filter(function (e) {
                                return e.enabled
                            })),
                                B(
                                    V(
                                        [].concat(s, a.options.modifiers),
                                        function (e) {
                                            return e.name
                                        },
                                    ),
                                ),
                                H(a.options.placement) === D &&
                                    (a.orderedModifiers.find(function (e) {
                                        return e.name === `flip`
                                    }) ||
                                        console.error(
                                            [
                                                `Popper: "auto" placements require the "flip" modifier be`,
                                                `present and enabled to work.`,
                                            ].join(` `),
                                        )))
                            var c = f(t)
                            return (
                                [
                                    c.marginTop,
                                    c.marginRight,
                                    c.marginBottom,
                                    c.marginLeft,
                                ].some(function (e) {
                                    return parseFloat(e)
                                }) &&
                                    console.warn(
                                        [
                                            `Popper: CSS "margin" styles cannot be used to apply padding`,
                                            `between the popper and its reference element or boundary.`,
                                            'To replicate margin, use the `offset` modifier, as well as',
                                            'the `padding` option in the `preventOverflow` and `flip`',
                                            `modifiers.`,
                                        ].join(` `),
                                    ),
                                u(),
                                l.update()
                            )
                        },
                        forceUpdate: function () {
                            if (!c) {
                                var e = a.elements,
                                    t = e.reference,
                                    n = e.popper
                                if (!ve(t, n)) {
                                    console.error(ge)
                                    return
                                }
                                ;((a.rects = {
                                    reference: m(
                                        t,
                                        S(n),
                                        a.options.strategy === `fixed`,
                                    ),
                                    popper: h(n),
                                }),
                                    (a.reset = !1),
                                    (a.placement = a.options.placement),
                                    a.orderedModifiers.forEach(function (e) {
                                        return (a.modifiersData[e.name] =
                                            Object.assign({}, e.data))
                                    }))
                                for (
                                    var r = 0, i = 0;
                                    i < a.orderedModifiers.length;
                                    i++
                                ) {
                                    if (((r += 1), r > 100)) {
                                        console.error(Z)
                                        break
                                    }
                                    if (a.reset === !0) {
                                        ;((a.reset = !1), (i = -1))
                                        continue
                                    }
                                    var o = a.orderedModifiers[i],
                                        s = o.fn,
                                        u = o.options,
                                        d = u === void 0 ? {} : u,
                                        f = o.name
                                    typeof s == `function` &&
                                        (a =
                                            s({
                                                state: a,
                                                options: d,
                                                name: f,
                                                instance: l,
                                            }) || a)
                                }
                            }
                        },
                        update: ne(function () {
                            return new Promise(function (e) {
                                ;(l.forceUpdate(), e(a))
                            })
                        }),
                        destroy: function () {
                            ;(d(), (c = !0))
                        },
                    }
                if (!ve(e, t)) return (console.error(ge), l)
                l.setOptions(n).then(function (e) {
                    !c && n.onFirstUpdate && n.onFirstUpdate(e)
                })
                function u() {
                    a.orderedModifiers.forEach(function (e) {
                        var t = e.name,
                            n = e.options,
                            r = n === void 0 ? {} : n,
                            i = e.effect
                        if (typeof i == `function`) {
                            var o = i({
                                state: a,
                                name: t,
                                instance: l,
                                options: r,
                            })
                            s.push(o || function () {})
                        }
                    })
                }
                function d() {
                    ;(s.forEach(function (e) {
                        return e()
                    }),
                        (s = []))
                }
                return l
            }
        }
        var be = { passive: !0 }
        function xe(e) {
            var t = e.state,
                r = e.instance,
                i = e.options,
                a = i.scroll,
                o = a === void 0 ? !0 : a,
                s = i.resize,
                c = s === void 0 ? !0 : s,
                l = n(t.elements.popper),
                u = [].concat(t.scrollParents.reference, t.scrollParents.popper)
            return (
                o &&
                    u.forEach(function (e) {
                        e.addEventListener(`scroll`, r.update, be)
                    }),
                c && l.addEventListener(`resize`, r.update, be),
                function () {
                    ;(o &&
                        u.forEach(function (e) {
                            e.removeEventListener(`scroll`, r.update, be)
                        }),
                        c && l.removeEventListener(`resize`, r.update, be))
                }
            )
        }
        var Se = {
            name: `eventListeners`,
            enabled: !0,
            phase: `write`,
            fn: function () {},
            effect: xe,
            data: {},
        }
        function Ce(e) {
            var t = e.state,
                n = e.name
            t.modifiersData[n] = de({
                reference: t.rects.reference,
                element: t.rects.popper,
                strategy: `absolute`,
                placement: t.placement,
            })
        }
        var we = {
                name: `popperOffsets`,
                enabled: !0,
                phase: `read`,
                fn: Ce,
                data: {},
            },
            Te = { top: `auto`, right: `auto`, bottom: `auto`, left: `auto` }
        function Ee(e) {
            var t = e.x,
                n = e.y,
                r = window.devicePixelRatio || 1
            return { x: q(q(t * r) / r) || 0, y: q(q(n * r) / r) || 0 }
        }
        function De(e) {
            var t,
                r = e.popper,
                i = e.popperRect,
                a = e.placement,
                o = e.offsets,
                s = e.position,
                c = e.gpuAcceleration,
                l = e.adaptive,
                d = e.roundOffsets,
                p = d === !0 ? Ee(o) : typeof d == `function` ? d(o) : o,
                m = p.x,
                h = m === void 0 ? 0 : m,
                g = p.y,
                _ = g === void 0 ? 0 : g,
                v = o.hasOwnProperty(`x`),
                y = o.hasOwnProperty(`y`),
                b = E,
                x = C,
                D = window
            if (l) {
                var O = S(r),
                    k = `clientHeight`,
                    A = `clientWidth`
                ;(O === n(r) &&
                    ((O = u(r)),
                    f(O).position !== `static` &&
                        ((k = `scrollHeight`), (A = `scrollWidth`))),
                    (O = O),
                    a === C &&
                        ((x = w), (_ -= O[k] - i.height), (_ *= c ? 1 : -1)),
                    a === E &&
                        ((b = T), (h -= O[A] - i.width), (h *= c ? 1 : -1)))
            }
            var j = Object.assign({ position: s }, l && Te)
            if (c) {
                var M
                return Object.assign(
                    {},
                    j,
                    ((M = {}),
                    (M[x] = y ? `0` : ``),
                    (M[b] = v ? `0` : ``),
                    (M.transform =
                        (D.devicePixelRatio || 1) < 2
                            ? `translate(` + h + `px, ` + _ + `px)`
                            : `translate3d(` + h + `px, ` + _ + `px, 0)`),
                    M),
                )
            }
            return Object.assign(
                {},
                j,
                ((t = {}),
                (t[x] = y ? _ + `px` : ``),
                (t[b] = v ? h + `px` : ``),
                (t.transform = ``),
                t),
            )
        }
        function Oe(e) {
            var t = e.state,
                n = e.options,
                r = n.gpuAcceleration,
                i = r === void 0 ? !0 : r,
                a = n.adaptive,
                o = a === void 0 ? !0 : a,
                s = n.roundOffsets,
                c = s === void 0 ? !0 : s,
                l = f(t.elements.popper).transitionProperty || ``
            o &&
                [`transform`, `top`, `right`, `bottom`, `left`].some(
                    function (e) {
                        return l.indexOf(e) >= 0
                    },
                ) &&
                console.warn(
                    [
                        `Popper: Detected CSS transitions on at least one of the following`,
                        `CSS properties: "transform", "top", "right", "bottom", "left".`,
                        `

`,
                        'Disable the "computeStyles" modifier\'s `adaptive` option to allow',
                        `for smooth transitions, or remove these properties from the CSS`,
                        `transition declaration on the popper element if only transitioning`,
                        `opacity or background-color for example.`,
                        `

`,
                        `We recommend using the popper element as a wrapper around an inner`,
                        `element that can have any CSS property transitioned for animations.`,
                    ].join(` `),
                )
            var u = {
                placement: H(t.placement),
                popper: t.elements.popper,
                popperRect: t.rects.popper,
                gpuAcceleration: i,
            }
            ;(t.modifiersData.popperOffsets != null &&
                (t.styles.popper = Object.assign(
                    {},
                    t.styles.popper,
                    De(
                        Object.assign({}, u, {
                            offsets: t.modifiersData.popperOffsets,
                            position: t.options.strategy,
                            adaptive: o,
                            roundOffsets: c,
                        }),
                    ),
                )),
                t.modifiersData.arrow != null &&
                    (t.styles.arrow = Object.assign(
                        {},
                        t.styles.arrow,
                        De(
                            Object.assign({}, u, {
                                offsets: t.modifiersData.arrow,
                                position: `absolute`,
                                adaptive: !1,
                                roundOffsets: c,
                            }),
                        ),
                    )),
                (t.attributes.popper = Object.assign({}, t.attributes.popper, {
                    'data-popper-placement': t.placement,
                })))
        }
        var ke = {
            name: `computeStyles`,
            enabled: !0,
            phase: `beforeWrite`,
            fn: Oe,
            data: {},
        }
        function Ae(e) {
            var t = e.state
            Object.keys(t.elements).forEach(function (e) {
                var n = t.styles[e] || {},
                    r = t.attributes[e] || {},
                    i = t.elements[e]
                !a(i) ||
                    !l(i) ||
                    (Object.assign(i.style, n),
                    Object.keys(r).forEach(function (e) {
                        var t = r[e]
                        t === !1
                            ? i.removeAttribute(e)
                            : i.setAttribute(e, t === !0 ? `` : t)
                    }))
            })
        }
        function je(e) {
            var t = e.state,
                n = {
                    popper: {
                        position: t.options.strategy,
                        left: `0`,
                        top: `0`,
                        margin: `0`,
                    },
                    arrow: { position: `absolute` },
                    reference: {},
                }
            return (
                Object.assign(t.elements.popper.style, n.popper),
                (t.styles = n),
                t.elements.arrow &&
                    Object.assign(t.elements.arrow.style, n.arrow),
                function () {
                    Object.keys(t.elements).forEach(function (e) {
                        var r = t.elements[e],
                            i = t.attributes[e] || {},
                            o = Object.keys(
                                t.styles.hasOwnProperty(e) ? t.styles[e] : n[e],
                            ).reduce(function (e, t) {
                                return ((e[t] = ``), e)
                            }, {})
                        !a(r) ||
                            !l(r) ||
                            (Object.assign(r.style, o),
                            Object.keys(i).forEach(function (e) {
                                r.removeAttribute(e)
                            }))
                    })
                }
            )
        }
        var Me = {
            name: `applyStyles`,
            enabled: !0,
            phase: `write`,
            fn: Ae,
            effect: je,
            requires: [`computeStyles`],
        }
        function Ne(e, t, n) {
            var r = H(e),
                i = [E, C].indexOf(r) >= 0 ? -1 : 1,
                a =
                    typeof n == `function`
                        ? n(Object.assign({}, t, { placement: e }))
                        : n,
                o = a[0],
                s = a[1]
            return (
                (o ||= 0),
                (s = (s || 0) * i),
                [E, T].indexOf(r) >= 0 ? { x: s, y: o } : { x: o, y: s }
            )
        }
        function Pe(e) {
            var t = e.state,
                n = e.options,
                r = e.name,
                i = n.offset,
                a = i === void 0 ? [0, 0] : i,
                o = F.reduce(function (e, n) {
                    return ((e[n] = Ne(n, t.rects, a)), e)
                }, {}),
                s = o[t.placement],
                c = s.x,
                l = s.y
            ;(t.modifiersData.popperOffsets != null &&
                ((t.modifiersData.popperOffsets.x += c),
                (t.modifiersData.popperOffsets.y += l)),
                (t.modifiersData[r] = o))
        }
        var Fe = {
                name: `offset`,
                enabled: !0,
                phase: `main`,
                requires: [`popperOffsets`],
                fn: Pe,
            },
            Ie = { left: `right`, right: `left`, bottom: `top`, top: `bottom` }
        function Le(e) {
            return e.replace(/left|right|bottom|top/g, function (e) {
                return Ie[e]
            })
        }
        var Re = { start: `end`, end: `start` }
        function ze(e) {
            return e.replace(/start|end/g, function (e) {
                return Re[e]
            })
        }
        function Be(e, t) {
            t === void 0 && (t = {})
            var n = t,
                r = n.placement,
                i = n.boundary,
                a = n.rootBoundary,
                o = n.padding,
                s = n.flipVariations,
                c = n.allowedAutoPlacements,
                l = c === void 0 ? F : c,
                u = ue(r),
                d = u
                    ? s
                        ? P
                        : P.filter(function (e) {
                              return ue(e) === u
                          })
                    : O,
                f = d.filter(function (e) {
                    return l.indexOf(e) >= 0
                })
            f.length === 0 &&
                ((f = d),
                console.error(
                    [
                        'Popper: The `allowedAutoPlacements` option did not allow any',
                        'placements. Ensure the `placement` option matches the variation',
                        `of the allowed placements.`,
                        `For example, "auto" cannot be used to allow "bottom-start".`,
                        `Use "auto-start" instead.`,
                    ].join(` `),
                ))
            var p = f.reduce(function (t, n) {
                return (
                    (t[n] = he(e, {
                        placement: n,
                        boundary: i,
                        rootBoundary: a,
                        padding: o,
                    })[H(n)]),
                    t
                )
            }, {})
            return Object.keys(p).sort(function (e, t) {
                return p[e] - p[t]
            })
        }
        function Ve(e) {
            if (H(e) === D) return []
            var t = Le(e)
            return [ze(e), t, ze(t)]
        }
        function He(e) {
            var t = e.state,
                n = e.options,
                r = e.name
            if (!t.modifiersData[r]._skip) {
                for (
                    var i = n.mainAxis,
                        a = i === void 0 ? !0 : i,
                        o = n.altAxis,
                        s = o === void 0 ? !0 : o,
                        c = n.fallbackPlacements,
                        l = n.padding,
                        u = n.boundary,
                        d = n.rootBoundary,
                        f = n.altBoundary,
                        p = n.flipVariations,
                        m = p === void 0 ? !0 : p,
                        h = n.allowedAutoPlacements,
                        g = t.options.placement,
                        _ = H(g) === g,
                        v = c || (_ || !m ? [Le(g)] : Ve(g)),
                        y = [g].concat(v).reduce(function (e, n) {
                            return e.concat(
                                H(n) === D
                                    ? Be(t, {
                                          placement: n,
                                          boundary: u,
                                          rootBoundary: d,
                                          padding: l,
                                          flipVariations: m,
                                          allowedAutoPlacements: h,
                                      })
                                    : n,
                            )
                        }, []),
                        b = t.rects.reference,
                        x = t.rects.popper,
                        S = new Map(),
                        O = !0,
                        A = y[0],
                        j = 0;
                    j < y.length;
                    j++
                ) {
                    var M = y[j],
                        N = H(M),
                        ee = ue(M) === k,
                        P = [C, w].indexOf(N) >= 0,
                        F = P ? `width` : `height`,
                        I = he(t, {
                            placement: M,
                            boundary: u,
                            rootBoundary: d,
                            altBoundary: f,
                            padding: l,
                        }),
                        L = P ? (ee ? T : E) : ee ? w : C
                    b[F] > x[F] && (L = Le(L))
                    var te = Le(L),
                        ne = []
                    if (
                        (a && ne.push(I[N] <= 0),
                        s && ne.push(I[L] <= 0, I[te] <= 0),
                        ne.every(function (e) {
                            return e
                        }))
                    ) {
                        ;((A = M), (O = !1))
                        break
                    }
                    S.set(M, ne)
                }
                if (O)
                    for (
                        var R = m ? 3 : 1,
                            z = function (e) {
                                var t = y.find(function (t) {
                                    var n = S.get(t)
                                    if (n)
                                        return n
                                            .slice(0, e)
                                            .every(function (e) {
                                                return e
                                            })
                                })
                                if (t) return ((A = t), `break`)
                            },
                            re = R;
                        re > 0 && z(re) !== `break`;
                        re--
                    );
                t.placement !== A &&
                    ((t.modifiersData[r]._skip = !0),
                    (t.placement = A),
                    (t.reset = !0))
            }
        }
        var Ue = {
            name: `flip`,
            enabled: !0,
            phase: `main`,
            fn: He,
            requiresIfExists: [`offset`],
            data: { _skip: !1 },
        }
        function We(e) {
            return e === `x` ? `y` : `x`
        }
        function Ge(e, t, n) {
            return G(e, K(t, n))
        }
        function Ke(e) {
            var t = e.state,
                n = e.options,
                r = e.name,
                i = n.mainAxis,
                a = i === void 0 ? !0 : i,
                o = n.altAxis,
                s = o === void 0 ? !1 : o,
                c = n.boundary,
                l = n.rootBoundary,
                u = n.altBoundary,
                d = n.padding,
                f = n.tether,
                p = f === void 0 ? !0 : f,
                m = n.tetherOffset,
                g = m === void 0 ? 0 : m,
                _ = he(t, {
                    boundary: c,
                    rootBoundary: l,
                    padding: d,
                    altBoundary: u,
                }),
                v = H(t.placement),
                y = ue(t.placement),
                b = !y,
                x = X(v),
                D = We(x),
                O = t.modifiersData.popperOffsets,
                A = t.rects.reference,
                j = t.rects.popper,
                M =
                    typeof g == `function`
                        ? g(
                              Object.assign({}, t.rects, {
                                  placement: t.placement,
                              }),
                          )
                        : g,
                N = { x: 0, y: 0 }
            if (O) {
                if (a || s) {
                    var ee = x === `y` ? C : E,
                        P = x === `y` ? w : T,
                        F = x === `y` ? `height` : `width`,
                        I = O[x],
                        L = O[x] + _[ee],
                        te = O[x] - _[P],
                        ne = p ? -j[F] / 2 : 0,
                        R = y === k ? A[F] : j[F],
                        z = y === k ? -j[F] : -A[F],
                        re = t.elements.arrow,
                        ie = p && re ? h(re) : { width: 0, height: 0 },
                        B = t.modifiersData[`arrow#persistent`]
                            ? t.modifiersData[`arrow#persistent`].padding
                            : fe(),
                        V = B[ee],
                        U = B[P],
                        W = Ge(0, A[F], ie[F]),
                        q = b ? A[F] / 2 - ne - W - V - M : R - W - V - M,
                        ae = b ? -A[F] / 2 + ne + W + U + M : z + W + U + M,
                        oe = t.elements.arrow && S(t.elements.arrow),
                        se = oe
                            ? x === `y`
                                ? oe.clientTop || 0
                                : oe.clientLeft || 0
                            : 0,
                        ce = t.modifiersData.offset
                            ? t.modifiersData.offset[t.placement][x]
                            : 0,
                        J = O[x] + q - ce - se,
                        le = O[x] + ae - ce
                    if (a) {
                        var Y = Ge(p ? K(L, J) : L, I, p ? G(te, le) : te)
                        ;((O[x] = Y), (N[x] = Y - I))
                    }
                    if (s) {
                        var de = x === `x` ? C : E,
                            pe = x === `x` ? w : T,
                            me = O[D],
                            ge = me + _[de],
                            Z = me - _[pe],
                            _e = Ge(p ? K(ge, J) : ge, me, p ? G(Z, le) : Z)
                        ;((O[D] = _e), (N[D] = _e - me))
                    }
                }
                t.modifiersData[r] = N
            }
        }
        var qe = {
                name: `preventOverflow`,
                enabled: !0,
                phase: `main`,
                fn: Ke,
                requiresIfExists: [`offset`],
            },
            Je = function (e, t) {
                return (
                    (e =
                        typeof e == `function`
                            ? e(
                                  Object.assign({}, t.rects, {
                                      placement: t.placement,
                                  }),
                              )
                            : e),
                    pe(typeof e == `number` ? me(e, O) : e)
                )
            }
        function Ye(e) {
            var t,
                n = e.state,
                r = e.name,
                i = e.options,
                a = n.elements.arrow,
                o = n.modifiersData.popperOffsets,
                s = H(n.placement),
                c = X(s),
                l = [E, T].indexOf(s) >= 0 ? `height` : `width`
            if (!(!a || !o)) {
                var u = Je(i.padding, n),
                    d = h(a),
                    f = c === `y` ? C : E,
                    p = c === `y` ? w : T,
                    m =
                        n.rects.reference[l] +
                        n.rects.reference[c] -
                        o[c] -
                        n.rects.popper[l],
                    g = o[c] - n.rects.reference[c],
                    _ = S(a),
                    v = _
                        ? c === `y`
                            ? _.clientHeight || 0
                            : _.clientWidth || 0
                        : 0,
                    y = m / 2 - g / 2,
                    b = u[f],
                    x = v - d[l] - u[p],
                    D = v / 2 - d[l] / 2 + y,
                    O = Ge(b, D, x),
                    k = c
                n.modifiersData[r] =
                    ((t = {}), (t[k] = O), (t.centerOffset = O - D), t)
            }
        }
        function Xe(e) {
            var t = e.state,
                n = e.options.element,
                r = n === void 0 ? `[data-popper-arrow]` : n
            if (
                r != null &&
                !(
                    typeof r == `string` &&
                    ((r = t.elements.popper.querySelector(r)), !r)
                )
            ) {
                if (
                    (a(r) ||
                        console.error(
                            [
                                `Popper: "arrow" element must be an HTMLElement (not an SVGElement).`,
                                `To use an SVG arrow, wrap it in an HTMLElement that will be used as`,
                                `the arrow.`,
                            ].join(` `),
                        ),
                    !oe(t.elements.popper, r))
                ) {
                    console.error(
                        [
                            'Popper: "arrow" modifier\'s `element` must be a child of the popper',
                            `element.`,
                        ].join(` `),
                    )
                    return
                }
                t.elements.arrow = r
            }
        }
        var Ze = {
            name: `arrow`,
            enabled: !0,
            phase: `main`,
            fn: Ye,
            effect: Xe,
            requires: [`popperOffsets`],
            requiresIfExists: [`preventOverflow`],
        }
        function Qe(e, t, n) {
            return (
                n === void 0 && (n = { x: 0, y: 0 }),
                {
                    top: e.top - t.height - n.y,
                    right: e.right - t.width + n.x,
                    bottom: e.bottom - t.height + n.y,
                    left: e.left - t.width - n.x,
                }
            )
        }
        function $e(e) {
            return [C, T, w, E].some(function (t) {
                return e[t] >= 0
            })
        }
        function et(e) {
            var t = e.state,
                n = e.name,
                r = t.rects.reference,
                i = t.rects.popper,
                a = t.modifiersData.preventOverflow,
                o = he(t, { elementContext: `reference` }),
                s = he(t, { altBoundary: !0 }),
                c = Qe(o, r),
                l = Qe(s, i, a),
                u = $e(c),
                d = $e(l)
            ;((t.modifiersData[n] = {
                referenceClippingOffsets: c,
                popperEscapeOffsets: l,
                isReferenceHidden: u,
                hasPopperEscaped: d,
            }),
                (t.attributes.popper = Object.assign({}, t.attributes.popper, {
                    'data-popper-reference-hidden': u,
                    'data-popper-escaped': d,
                })))
        }
        var tt = {
                name: `hide`,
                enabled: !0,
                phase: `main`,
                requiresIfExists: [`preventOverflow`],
                fn: et,
            },
            nt = ye({ defaultModifiers: [Se, we, ke, Me] }),
            rt = [Se, we, ke, Me, Fe, Ue, qe, Ze, tt],
            it = ye({ defaultModifiers: rt })
        ;((e.applyStyles = Me),
            (e.arrow = Ze),
            (e.computeStyles = ke),
            (e.createPopper = it),
            (e.createPopperLite = nt),
            (e.defaultModifiers = rt),
            (e.detectOverflow = he),
            (e.eventListeners = Se),
            (e.flip = Ue),
            (e.hide = tt),
            (e.offset = Fe),
            (e.popperGenerator = ye),
            (e.popperOffsets = we),
            (e.preventOverflow = qe))
    }),
    lt = at((e) => {
        Object.defineProperty(e, `__esModule`, { value: !0 })
        var t = ct(),
            n = `<svg width="16" height="6" xmlns="http://www.w3.org/2000/svg"><path d="M0 6s1.796-.013 4.67-3.615C5.851.9 6.93.006 8 0c1.07-.006 2.148.887 3.343 2.385C14.233 6.005 16 6 16 6H0z"></svg>`,
            r = `tippy-box`,
            i = `tippy-content`,
            a = `tippy-backdrop`,
            o = `tippy-arrow`,
            s = `tippy-svg-arrow`,
            c = { passive: !0, capture: !0 }
        function l(e, t) {
            return {}.hasOwnProperty.call(e, t)
        }
        function u(e, t, n) {
            return Array.isArray(e)
                ? (e[t] ?? (Array.isArray(n) ? n[t] : n))
                : e
        }
        function d(e, t) {
            var n = {}.toString.call(e)
            return n.indexOf(`[object`) === 0 && n.indexOf(t + `]`) > -1
        }
        function f(e, t) {
            return typeof e == `function` ? e.apply(void 0, t) : e
        }
        function p(e, t) {
            if (t === 0) return e
            var n
            return function (r) {
                ;(clearTimeout(n),
                    (n = setTimeout(function () {
                        e(r)
                    }, t)))
            }
        }
        function m(e, t) {
            var n = Object.assign({}, e)
            return (
                t.forEach(function (e) {
                    delete n[e]
                }),
                n
            )
        }
        function h(e) {
            return e.split(/\s+/).filter(Boolean)
        }
        function g(e) {
            return [].concat(e)
        }
        function _(e, t) {
            e.indexOf(t) === -1 && e.push(t)
        }
        function v(e) {
            return e.filter(function (t, n) {
                return e.indexOf(t) === n
            })
        }
        function y(e) {
            return e.split(`-`)[0]
        }
        function b(e) {
            return [].slice.call(e)
        }
        function x(e) {
            return Object.keys(e).reduce(function (t, n) {
                return (e[n] !== void 0 && (t[n] = e[n]), t)
            }, {})
        }
        function S() {
            return document.createElement(`div`)
        }
        function C(e) {
            return [`Element`, `Fragment`].some(function (t) {
                return d(e, t)
            })
        }
        function w(e) {
            return d(e, `NodeList`)
        }
        function T(e) {
            return d(e, `MouseEvent`)
        }
        function E(e) {
            return !!(e && e._tippy && e._tippy.reference === e)
        }
        function D(e) {
            return C(e)
                ? [e]
                : w(e)
                  ? b(e)
                  : Array.isArray(e)
                    ? e
                    : b(document.querySelectorAll(e))
        }
        function O(e, t) {
            e.forEach(function (e) {
                e && (e.style.transitionDuration = t + `ms`)
            })
        }
        function k(e, t) {
            e.forEach(function (e) {
                e && e.setAttribute(`data-state`, t)
            })
        }
        function A(e) {
            var t = g(e)[0]
            return t != null && t.ownerDocument?.body
                ? t.ownerDocument
                : document
        }
        function j(e, t) {
            var n = t.clientX,
                r = t.clientY
            return e.every(function (e) {
                var t = e.popperRect,
                    i = e.popperState,
                    a = e.props.interactiveBorder,
                    o = y(i.placement),
                    s = i.modifiersData.offset
                if (!s) return !0
                var c = o === `bottom` ? s.top.y : 0,
                    l = o === `top` ? s.bottom.y : 0,
                    u = o === `right` ? s.left.x : 0,
                    d = o === `left` ? s.right.x : 0,
                    f = t.top - r + c > a,
                    p = r - t.bottom - l > a,
                    m = t.left - n + u > a,
                    h = n - t.right - d > a
                return f || p || m || h
            })
        }
        function M(e, t, n) {
            var r = t + `EventListener`
            ;[`transitionend`, `webkitTransitionEnd`].forEach(function (t) {
                e[r](t, n)
            })
        }
        var N = { isTouch: !1 },
            ee = 0
        function P() {
            N.isTouch ||
                ((N.isTouch = !0),
                window.performance && document.addEventListener(`mousemove`, F))
        }
        function F() {
            var e = performance.now()
            ;(e - ee < 20 &&
                ((N.isTouch = !1),
                document.removeEventListener(`mousemove`, F)),
                (ee = e))
        }
        function I() {
            var e = document.activeElement
            if (E(e)) {
                var t = e._tippy
                e.blur && !t.state.isVisible && e.blur()
            }
        }
        function L() {
            ;(document.addEventListener(`touchstart`, P, c),
                window.addEventListener(`blur`, I))
        }
        var te =
                typeof window < `u` && typeof document < `u`
                    ? navigator.userAgent
                    : ``,
            ne = /MSIE |Trident\//.test(te)
        function R(e) {
            var t = e === `destroy` ? `n already-` : ` `
            return [
                e +
                    `() was called on a` +
                    t +
                    `destroyed instance. This is a no-op but`,
                `indicates a potential memory leak.`,
            ].join(` `)
        }
        function z(e) {
            return e
                .replace(/[ \t]{2,}/g, ` `)
                .replace(/^[ \t]*/gm, ``)
                .trim()
        }
        function re(e) {
            return z(
                `
  %ctippy.js

  %c` +
                    z(e) +
                    `

  %c👷‍ This is a development-only message. It will be removed in production.
  `,
            )
        }
        function ie(e) {
            return [
                re(e),
                `color: #00C584; font-size: 1.3em; font-weight: bold;`,
                `line-height: 1.5`,
                `color: #a6a095;`,
            ]
        }
        var B
        V()
        function V() {
            B = new Set()
        }
        function H(e, t) {
            if (e && !B.has(t)) {
                var n
                ;(B.add(t), (n = console).warn.apply(n, ie(t)))
            }
        }
        function U(e, t) {
            if (e && !B.has(t)) {
                var n
                ;(B.add(t), (n = console).error.apply(n, ie(t)))
            }
        }
        function W(e) {
            var t = !e,
                n =
                    Object.prototype.toString.call(e) === `[object Object]` &&
                    !e.addEventListener
            ;(U(
                t,
                [
                    `tippy() was passed`,
                    '`' + String(e) + '`',
                    `as its targets (first) argument. Valid types are: String, Element,`,
                    `Element[], or NodeList.`,
                ].join(` `),
            ),
                U(
                    n,
                    [
                        `tippy() was passed a plain object which is not supported as an argument`,
                        `for virtual positioning. Use props.getReferenceClientRect instead.`,
                    ].join(` `),
                ))
        }
        var G = {
                animateFill: !1,
                followCursor: !1,
                inlinePositioning: !1,
                sticky: !1,
            },
            K = Object.assign(
                {
                    appendTo: function () {
                        return document.body
                    },
                    aria: { content: `auto`, expanded: `auto` },
                    delay: 0,
                    duration: [300, 250],
                    getReferenceClientRect: null,
                    hideOnClick: !0,
                    ignoreAttributes: !1,
                    interactive: !1,
                    interactiveBorder: 2,
                    interactiveDebounce: 0,
                    moveTransition: ``,
                    offset: [0, 10],
                    onAfterUpdate: function () {},
                    onBeforeUpdate: function () {},
                    onCreate: function () {},
                    onDestroy: function () {},
                    onHidden: function () {},
                    onHide: function () {},
                    onMount: function () {},
                    onShow: function () {},
                    onShown: function () {},
                    onTrigger: function () {},
                    onUntrigger: function () {},
                    onClickOutside: function () {},
                    placement: `top`,
                    plugins: [],
                    popperOptions: {},
                    render: null,
                    showOnCreate: !1,
                    touch: !0,
                    trigger: `mouseenter focus`,
                    triggerTarget: null,
                },
                G,
                {},
                {
                    allowHTML: !1,
                    animation: `fade`,
                    arrow: !0,
                    content: ``,
                    inertia: !1,
                    maxWidth: 350,
                    role: `tooltip`,
                    theme: ``,
                    zIndex: 9999,
                },
            ),
            q = Object.keys(K),
            ae = function (e) {
                ;(J(e, []),
                    Object.keys(e).forEach(function (t) {
                        K[t] = e[t]
                    }))
            }
        function oe(e) {
            var t = (e.plugins || []).reduce(function (t, n) {
                var r = n.name,
                    i = n.defaultValue
                return (r && (t[r] = e[r] === void 0 ? i : e[r]), t)
            }, {})
            return Object.assign({}, e, {}, t)
        }
        function se(e, t) {
            return (
                t ? Object.keys(oe(Object.assign({}, K, { plugins: t }))) : q
            ).reduce(function (t, n) {
                var r = (e.getAttribute(`data-tippy-` + n) || ``).trim()
                if (!r) return t
                if (n === `content`) t[n] = r
                else
                    try {
                        t[n] = JSON.parse(r)
                    } catch {
                        t[n] = r
                    }
                return t
            }, {})
        }
        function ce(e, t) {
            var n = Object.assign(
                {},
                t,
                { content: f(t.content, [e]) },
                t.ignoreAttributes ? {} : se(e, t.plugins),
            )
            return (
                (n.aria = Object.assign({}, K.aria, {}, n.aria)),
                (n.aria = {
                    expanded:
                        n.aria.expanded === `auto`
                            ? t.interactive
                            : n.aria.expanded,
                    content:
                        n.aria.content === `auto`
                            ? t.interactive
                                ? null
                                : `describedby`
                            : n.aria.content,
                }),
                n
            )
        }
        function J(e, t) {
            ;(e === void 0 && (e = {}),
                t === void 0 && (t = []),
                Object.keys(e).forEach(function (e) {
                    var n = !l(m(K, Object.keys(G)), e)
                    ;((n &&=
                        t.filter(function (t) {
                            return t.name === e
                        }).length === 0),
                        H(
                            n,
                            [
                                '`' + e + '`',
                                `is not a valid prop. You may have spelled it incorrectly, or if it's`,
                                `a plugin, forgot to pass it in an array as props.plugins.`,
                                `

`,
                                `All props: https://atomiks.github.io/tippyjs/v6/all-props/
`,
                                `Plugins: https://atomiks.github.io/tippyjs/v6/plugins/`,
                            ].join(` `),
                        ))
                }))
        }
        var le = function () {
            return `innerHTML`
        }
        function Y(e, t) {
            e[le()] = t
        }
        function ue(e) {
            var t = S()
            return (
                e === !0
                    ? (t.className = o)
                    : ((t.className = s), C(e) ? t.appendChild(e) : Y(t, e)),
                t
            )
        }
        function X(e, t) {
            C(t.content)
                ? (Y(e, ``), e.appendChild(t.content))
                : typeof t.content != `function` &&
                  (t.allowHTML ? Y(e, t.content) : (e.textContent = t.content))
        }
        function de(e) {
            var t = e.firstElementChild,
                n = b(t.children)
            return {
                box: t,
                content: n.find(function (e) {
                    return e.classList.contains(i)
                }),
                arrow: n.find(function (e) {
                    return e.classList.contains(o) || e.classList.contains(s)
                }),
                backdrop: n.find(function (e) {
                    return e.classList.contains(a)
                }),
            }
        }
        function fe(e) {
            var t = S(),
                n = S()
            ;((n.className = r),
                n.setAttribute(`data-state`, `hidden`),
                n.setAttribute(`tabindex`, `-1`))
            var a = S()
            ;((a.className = i),
                a.setAttribute(`data-state`, `hidden`),
                X(a, e.props),
                t.appendChild(n),
                n.appendChild(a),
                o(e.props, e.props))
            function o(n, r) {
                var i = de(t),
                    a = i.box,
                    o = i.content,
                    s = i.arrow
                ;(r.theme
                    ? a.setAttribute(`data-theme`, r.theme)
                    : a.removeAttribute(`data-theme`),
                    typeof r.animation == `string`
                        ? a.setAttribute(`data-animation`, r.animation)
                        : a.removeAttribute(`data-animation`),
                    r.inertia
                        ? a.setAttribute(`data-inertia`, ``)
                        : a.removeAttribute(`data-inertia`),
                    (a.style.maxWidth =
                        typeof r.maxWidth == `number`
                            ? r.maxWidth + `px`
                            : r.maxWidth),
                    r.role
                        ? a.setAttribute(`role`, r.role)
                        : a.removeAttribute(`role`),
                    (n.content !== r.content || n.allowHTML !== r.allowHTML) &&
                        X(o, e.props),
                    r.arrow
                        ? s
                            ? n.arrow !== r.arrow &&
                              (a.removeChild(s), a.appendChild(ue(r.arrow)))
                            : a.appendChild(ue(r.arrow))
                        : s && a.removeChild(s))
            }
            return { popper: t, onUpdate: o }
        }
        fe.$$tippy = !0
        var pe = 1,
            me = [],
            he = []
        function ge(e, n) {
            var r = ce(e, Object.assign({}, K, {}, oe(x(n)))),
                i,
                a,
                o,
                s = !1,
                l = !1,
                d = !1,
                m = !1,
                y,
                C,
                w,
                E = [],
                D = p(De, r.interactiveDebounce),
                ee,
                P = pe++,
                F = null,
                I = v(r.plugins),
                L = {
                    id: P,
                    reference: e,
                    popper: S(),
                    popperInstance: F,
                    props: r,
                    state: {
                        isEnabled: !0,
                        isVisible: !1,
                        isDestroyed: !1,
                        isMounted: !1,
                        isShown: !1,
                    },
                    plugins: I,
                    clearDelayTimeouts: ze,
                    setProps: Be,
                    setContent: Ve,
                    show: He,
                    hide: Ue,
                    hideWithInteractivity: We,
                    enable: Le,
                    disable: Re,
                    unmount: Ge,
                    destroy: Ke,
                }
            if (!r.render)
                return (U(!0, `render() function has not been supplied.`), L)
            var te = r.render(L),
                z = te.popper,
                re = te.onUpdate
            ;(z.setAttribute(`data-tippy-root`, ``),
                (z.id = `tippy-` + L.id),
                (L.popper = z),
                (e._tippy = L),
                (z._tippy = L))
            var ie = I.map(function (e) {
                    return e.fn(L)
                }),
                B = e.hasAttribute(`aria-expanded`)
            return (
                we(),
                X(),
                le(),
                Y(`onCreate`, [L]),
                r.showOnCreate && Fe(),
                z.addEventListener(`mouseenter`, function () {
                    L.props.interactive &&
                        L.state.isVisible &&
                        L.clearDelayTimeouts()
                }),
                z.addEventListener(`mouseleave`, function (e) {
                    L.props.interactive &&
                        L.props.trigger.indexOf(`mouseenter`) >= 0 &&
                        (ae().addEventListener(`mousemove`, D), D(e))
                }),
                L
            )
            function V() {
                var e = L.props.touch
                return Array.isArray(e) ? e : [e, 0]
            }
            function W() {
                return V()[0] === `hold`
            }
            function G() {
                return !!L.props.render?.$$tippy
            }
            function q() {
                return ee || e
            }
            function ae() {
                var e = q().parentNode
                return e ? A(e) : document
            }
            function se() {
                return de(z)
            }
            function J(e) {
                return (L.state.isMounted && !L.state.isVisible) ||
                    N.isTouch ||
                    (y && y.type === `focus`)
                    ? 0
                    : u(L.props.delay, e ? 0 : 1, K.delay)
            }
            function le() {
                ;((z.style.pointerEvents =
                    L.props.interactive && L.state.isVisible ? `` : `none`),
                    (z.style.zIndex = `` + L.props.zIndex))
            }
            function Y(e, t, n) {
                if (
                    (n === void 0 && (n = !0),
                    ie.forEach(function (n) {
                        n[e] && n[e].apply(void 0, t)
                    }),
                    n)
                ) {
                    var r
                    ;(r = L.props)[e].apply(r, t)
                }
            }
            function ue() {
                var t = L.props.aria
                if (t.content) {
                    var n = `aria-` + t.content,
                        r = z.id
                    g(L.props.triggerTarget || e).forEach(function (e) {
                        var t = e.getAttribute(n)
                        if (L.state.isVisible)
                            e.setAttribute(n, t ? t + ` ` + r : r)
                        else {
                            var i = t && t.replace(r, ``).trim()
                            i ? e.setAttribute(n, i) : e.removeAttribute(n)
                        }
                    })
                }
            }
            function X() {
                B ||
                    !L.props.aria.expanded ||
                    g(L.props.triggerTarget || e).forEach(function (e) {
                        L.props.interactive
                            ? e.setAttribute(
                                  `aria-expanded`,
                                  L.state.isVisible && e === q()
                                      ? `true`
                                      : `false`,
                              )
                            : e.removeAttribute(`aria-expanded`)
                    })
            }
            function fe() {
                ;(ae().removeEventListener(`mousemove`, D),
                    (me = me.filter(function (e) {
                        return e !== D
                    })))
            }
            function ge(e) {
                if (
                    !(N.isTouch && (d || e.type === `mousedown`)) &&
                    !(L.props.interactive && z.contains(e.target))
                ) {
                    if (q().contains(e.target)) {
                        if (
                            N.isTouch ||
                            (L.state.isVisible &&
                                L.props.trigger.indexOf(`click`) >= 0)
                        )
                            return
                    } else Y(`onClickOutside`, [L, e])
                    L.props.hideOnClick === !0 &&
                        (L.clearDelayTimeouts(),
                        L.hide(),
                        (l = !0),
                        setTimeout(function () {
                            l = !1
                        }),
                        L.state.isMounted || ye())
                }
            }
            function Z() {
                d = !0
            }
            function _e() {
                d = !1
            }
            function ve() {
                var e = ae()
                ;(e.addEventListener(`mousedown`, ge, !0),
                    e.addEventListener(`touchend`, ge, c),
                    e.addEventListener(`touchstart`, _e, c),
                    e.addEventListener(`touchmove`, Z, c))
            }
            function ye() {
                var e = ae()
                ;(e.removeEventListener(`mousedown`, ge, !0),
                    e.removeEventListener(`touchend`, ge, c),
                    e.removeEventListener(`touchstart`, _e, c),
                    e.removeEventListener(`touchmove`, Z, c))
            }
            function be(e, t) {
                Se(e, function () {
                    !L.state.isVisible &&
                        z.parentNode &&
                        z.parentNode.contains(z) &&
                        t()
                })
            }
            function xe(e, t) {
                Se(e, t)
            }
            function Se(e, t) {
                var n = se().box
                function r(e) {
                    e.target === n && (M(n, `remove`, r), t())
                }
                if (e === 0) return t()
                ;(M(n, `remove`, C), M(n, `add`, r), (C = r))
            }
            function Ce(t, n, r) {
                ;(r === void 0 && (r = !1),
                    g(L.props.triggerTarget || e).forEach(function (e) {
                        ;(e.addEventListener(t, n, r),
                            E.push({
                                node: e,
                                eventType: t,
                                handler: n,
                                options: r,
                            }))
                    }))
            }
            function we() {
                ;(W() &&
                    (Ce(`touchstart`, Ee, { passive: !0 }),
                    Ce(`touchend`, Oe, { passive: !0 })),
                    h(L.props.trigger).forEach(function (e) {
                        if (e !== `manual`)
                            switch ((Ce(e, Ee), e)) {
                                case `mouseenter`:
                                    Ce(`mouseleave`, Oe)
                                    break
                                case `focus`:
                                    Ce(ne ? `focusout` : `blur`, ke)
                                    break
                                case `focusin`:
                                    Ce(`focusout`, ke)
                                    break
                            }
                    }))
            }
            function Te() {
                ;(E.forEach(function (e) {
                    var t = e.node,
                        n = e.eventType,
                        r = e.handler,
                        i = e.options
                    t.removeEventListener(n, r, i)
                }),
                    (E = []))
            }
            function Ee(e) {
                var t = !1
                if (!(!L.state.isEnabled || Ae(e) || l)) {
                    var n = y?.type === `focus`
                    ;((y = e),
                        (ee = e.currentTarget),
                        X(),
                        !L.state.isVisible &&
                            T(e) &&
                            me.forEach(function (t) {
                                return t(e)
                            }),
                        e.type === `click` &&
                        (L.props.trigger.indexOf(`mouseenter`) < 0 || s) &&
                        L.props.hideOnClick !== !1 &&
                        L.state.isVisible
                            ? (t = !0)
                            : Fe(e),
                        e.type === `click` && (s = !t),
                        t && !n && Ie(e))
                }
            }
            function De(e) {
                var t = e.target,
                    n = q().contains(t) || z.contains(t)
                ;(e.type === `mousemove` && n) ||
                    (j(
                        Pe()
                            .concat(z)
                            .map(function (e) {
                                var t = e._tippy.popperInstance?.state
                                return t
                                    ? {
                                          popperRect: e.getBoundingClientRect(),
                                          popperState: t,
                                          props: r,
                                      }
                                    : null
                            })
                            .filter(Boolean),
                        e,
                    ) &&
                        (fe(), Ie(e)))
            }
            function Oe(e) {
                if (!(Ae(e) || (L.props.trigger.indexOf(`click`) >= 0 && s))) {
                    if (L.props.interactive) {
                        L.hideWithInteractivity(e)
                        return
                    }
                    Ie(e)
                }
            }
            function ke(e) {
                ;(L.props.trigger.indexOf(`focusin`) < 0 && e.target !== q()) ||
                    (L.props.interactive &&
                        e.relatedTarget &&
                        z.contains(e.relatedTarget)) ||
                    Ie(e)
            }
            function Ae(e) {
                return N.isTouch ? W() !== e.type.indexOf(`touch`) >= 0 : !1
            }
            function je() {
                Me()
                var n = L.props,
                    r = n.popperOptions,
                    i = n.placement,
                    a = n.offset,
                    o = n.getReferenceClientRect,
                    s = n.moveTransition,
                    c = G() ? de(z).arrow : null,
                    l = o
                        ? {
                              getBoundingClientRect: o,
                              contextElement: o.contextElement || q(),
                          }
                        : e,
                    u = [
                        { name: `offset`, options: { offset: a } },
                        {
                            name: `preventOverflow`,
                            options: {
                                padding: {
                                    top: 2,
                                    bottom: 2,
                                    left: 5,
                                    right: 5,
                                },
                            },
                        },
                        { name: `flip`, options: { padding: 5 } },
                        { name: `computeStyles`, options: { adaptive: !s } },
                        {
                            name: `$$tippy`,
                            enabled: !0,
                            phase: `beforeWrite`,
                            requires: [`computeStyles`],
                            fn: function (e) {
                                var t = e.state
                                if (G()) {
                                    var n = se().box
                                    ;([
                                        `placement`,
                                        `reference-hidden`,
                                        `escaped`,
                                    ].forEach(function (e) {
                                        e === `placement`
                                            ? n.setAttribute(
                                                  `data-placement`,
                                                  t.placement,
                                              )
                                            : t.attributes.popper[
                                                    `data-popper-` + e
                                                ]
                                              ? n.setAttribute(`data-` + e, ``)
                                              : n.removeAttribute(`data-` + e)
                                    }),
                                        (t.attributes.popper = {}))
                                }
                            },
                        },
                    ]
                ;(G() &&
                    c &&
                    u.push({
                        name: `arrow`,
                        options: { element: c, padding: 3 },
                    }),
                    u.push.apply(u, r?.modifiers || []),
                    (L.popperInstance = t.createPopper(
                        l,
                        z,
                        Object.assign({}, r, {
                            placement: i,
                            onFirstUpdate: w,
                            modifiers: u,
                        }),
                    )))
            }
            function Me() {
                L.popperInstance &&= (L.popperInstance.destroy(), null)
            }
            function Ne() {
                var e = L.props.appendTo,
                    t,
                    n = q()
                ;((t =
                    (L.props.interactive && e === K.appendTo) || e === `parent`
                        ? n.parentNode
                        : f(e, [n])),
                    t.contains(z) || t.appendChild(z),
                    je(),
                    H(
                        L.props.interactive &&
                            e === K.appendTo &&
                            n.nextElementSibling !== z,
                        [
                            `Interactive tippy element may not be accessible via keyboard`,
                            `navigation because it is not directly after the reference element`,
                            `in the DOM source order.`,
                            `

`,
                            `Using a wrapper <div> or <span> tag around the reference element`,
                            `solves this by creating a new parentNode context.`,
                            `

`,
                            'Specifying `appendTo: document.body` silences this warning, but it',
                            `assumes you are using a focus management solution to handle`,
                            `keyboard navigation.`,
                            `

`,
                            `See: https://atomiks.github.io/tippyjs/v6/accessibility/#interactivity`,
                        ].join(` `),
                    ))
            }
            function Pe() {
                return b(z.querySelectorAll(`[data-tippy-root]`))
            }
            function Fe(e) {
                ;(L.clearDelayTimeouts(), e && Y(`onTrigger`, [L, e]), ve())
                var t = J(!0),
                    n = V(),
                    r = n[0],
                    a = n[1]
                ;(N.isTouch && r === `hold` && a && (t = a),
                    t
                        ? (i = setTimeout(function () {
                              L.show()
                          }, t))
                        : L.show())
            }
            function Ie(e) {
                if (
                    (L.clearDelayTimeouts(),
                    Y(`onUntrigger`, [L, e]),
                    !L.state.isVisible)
                ) {
                    ye()
                    return
                }
                if (
                    !(
                        L.props.trigger.indexOf(`mouseenter`) >= 0 &&
                        L.props.trigger.indexOf(`click`) >= 0 &&
                        [`mouseleave`, `mousemove`].indexOf(e.type) >= 0 &&
                        s
                    )
                ) {
                    var t = J(!1)
                    t
                        ? (a = setTimeout(function () {
                              L.state.isVisible && L.hide()
                          }, t))
                        : (o = requestAnimationFrame(function () {
                              L.hide()
                          }))
                }
            }
            function Le() {
                L.state.isEnabled = !0
            }
            function Re() {
                ;(L.hide(), (L.state.isEnabled = !1))
            }
            function ze() {
                ;(clearTimeout(i), clearTimeout(a), cancelAnimationFrame(o))
            }
            function Be(t) {
                if (
                    (H(L.state.isDestroyed, R(`setProps`)),
                    !L.state.isDestroyed)
                ) {
                    ;(Y(`onBeforeUpdate`, [L, t]), Te())
                    var n = L.props,
                        r = ce(
                            e,
                            Object.assign({}, L.props, {}, t, {
                                ignoreAttributes: !0,
                            }),
                        )
                    ;((L.props = r),
                        we(),
                        n.interactiveDebounce !== r.interactiveDebounce &&
                            (fe(), (D = p(De, r.interactiveDebounce))),
                        n.triggerTarget && !r.triggerTarget
                            ? g(n.triggerTarget).forEach(function (e) {
                                  e.removeAttribute(`aria-expanded`)
                              })
                            : r.triggerTarget &&
                              e.removeAttribute(`aria-expanded`),
                        X(),
                        le(),
                        re && re(n, r),
                        L.popperInstance &&
                            (je(),
                            Pe().forEach(function (e) {
                                requestAnimationFrame(
                                    e._tippy.popperInstance.forceUpdate,
                                )
                            })),
                        Y(`onAfterUpdate`, [L, t]))
                }
            }
            function Ve(e) {
                L.setProps({ content: e })
            }
            function He() {
                H(L.state.isDestroyed, R(`show`))
                var e = L.state.isVisible,
                    t = L.state.isDestroyed,
                    n = !L.state.isEnabled,
                    r = N.isTouch && !L.props.touch,
                    i = u(L.props.duration, 0, K.duration)
                if (
                    !(e || t || n || r) &&
                    !q().hasAttribute(`disabled`) &&
                    (Y(`onShow`, [L], !1), L.props.onShow(L) !== !1)
                ) {
                    if (
                        ((L.state.isVisible = !0),
                        G() && (z.style.visibility = `visible`),
                        le(),
                        ve(),
                        L.state.isMounted || (z.style.transition = `none`),
                        G())
                    ) {
                        var a = se(),
                            o = a.box,
                            s = a.content
                        O([o, s], 0)
                    }
                    ;((w = function () {
                        var e
                        if (!(!L.state.isVisible || m)) {
                            if (
                                ((m = !0),
                                z.offsetHeight,
                                (z.style.transition = L.props.moveTransition),
                                G() && L.props.animation)
                            ) {
                                var t = se(),
                                    n = t.box,
                                    r = t.content
                                ;(O([n, r], i), k([n, r], `visible`))
                            }
                            ;(ue(),
                                X(),
                                _(he, L),
                                (e = L.popperInstance) == null ||
                                    e.forceUpdate(),
                                (L.state.isMounted = !0),
                                Y(`onMount`, [L]),
                                L.props.animation &&
                                    G() &&
                                    xe(i, function () {
                                        ;((L.state.isShown = !0),
                                            Y(`onShown`, [L]))
                                    }))
                        }
                    }),
                        Ne())
                }
            }
            function Ue() {
                H(L.state.isDestroyed, R(`hide`))
                var e = !L.state.isVisible,
                    t = L.state.isDestroyed,
                    n = !L.state.isEnabled,
                    r = u(L.props.duration, 1, K.duration)
                if (
                    !(e || t || n) &&
                    (Y(`onHide`, [L], !1), L.props.onHide(L) !== !1)
                ) {
                    if (
                        ((L.state.isVisible = !1),
                        (L.state.isShown = !1),
                        (m = !1),
                        (s = !1),
                        G() && (z.style.visibility = `hidden`),
                        fe(),
                        ye(),
                        le(),
                        G())
                    ) {
                        var i = se(),
                            a = i.box,
                            o = i.content
                        L.props.animation && (O([a, o], r), k([a, o], `hidden`))
                    }
                    ;(ue(),
                        X(),
                        L.props.animation
                            ? G() && be(r, L.unmount)
                            : L.unmount())
                }
            }
            function We(e) {
                ;(H(L.state.isDestroyed, R(`hideWithInteractivity`)),
                    ae().addEventListener(`mousemove`, D),
                    _(me, D),
                    D(e))
            }
            function Ge() {
                ;(H(L.state.isDestroyed, R(`unmount`)),
                    L.state.isVisible && L.hide(),
                    L.state.isMounted &&
                        (Me(),
                        Pe().forEach(function (e) {
                            e._tippy.unmount()
                        }),
                        z.parentNode && z.parentNode.removeChild(z),
                        (he = he.filter(function (e) {
                            return e !== L
                        })),
                        (L.state.isMounted = !1),
                        Y(`onHidden`, [L])))
            }
            function Ke() {
                ;(H(L.state.isDestroyed, R(`destroy`)),
                    !L.state.isDestroyed &&
                        (L.clearDelayTimeouts(),
                        L.unmount(),
                        Te(),
                        delete e._tippy,
                        (L.state.isDestroyed = !0),
                        Y(`onDestroy`, [L])))
            }
        }
        function Z(e, t) {
            t === void 0 && (t = {})
            var n = K.plugins.concat(t.plugins || [])
            ;(W(e), J(t, n), L())
            var r = Object.assign({}, t, { plugins: n }),
                i = D(e),
                a = C(r.content),
                o = i.length > 1
            H(
                a && o,
                [
                    'tippy() was passed an Element as the `content` prop, but more than',
                    `one tippy instance was created by this invocation. This means the`,
                    `content element will only be appended to the last tippy instance.`,
                    `

`,
                    `Instead, pass the .innerHTML of the element, or use a function that`,
                    `returns a cloned version of the element instead.`,
                    `

`,
                    `1) content: element.innerHTML
`,
                    `2) content: () => element.cloneNode(true)`,
                ].join(` `),
            )
            var s = i.reduce(function (e, t) {
                var n = t && ge(t, r)
                return (n && e.push(n), e)
            }, [])
            return C(e) ? s[0] : s
        }
        ;((Z.defaultProps = K), (Z.setDefaultProps = ae), (Z.currentInput = N))
        var _e = function (e) {
                var t = e === void 0 ? {} : e,
                    n = t.exclude,
                    r = t.duration
                he.forEach(function (e) {
                    var t = !1
                    if (
                        (n &&
                            (t = E(n)
                                ? e.reference === n
                                : e.popper === n.popper),
                        !t)
                    ) {
                        var i = e.props.duration
                        ;(e.setProps({ duration: r }),
                            e.hide(),
                            e.state.isDestroyed || e.setProps({ duration: i }))
                    }
                })
            },
            ve = Object.assign({}, t.applyStyles, {
                effect: function (e) {
                    var t = e.state,
                        n = {
                            popper: {
                                position: t.options.strategy,
                                left: `0`,
                                top: `0`,
                                margin: `0`,
                            },
                            arrow: { position: `absolute` },
                            reference: {},
                        }
                    ;(Object.assign(t.elements.popper.style, n.popper),
                        (t.styles = n),
                        t.elements.arrow &&
                            Object.assign(t.elements.arrow.style, n.arrow))
                },
            }),
            ye = function (e, t) {
                ;(t === void 0 && (t = {}),
                    U(
                        !Array.isArray(e),
                        [
                            `The first argument passed to createSingleton() must be an array of`,
                            `tippy instances. The passed value was`,
                            String(e),
                        ].join(` `),
                    ))
                var n = e,
                    r = [],
                    i,
                    a = t.overrides,
                    o = [],
                    s = !1
                function c() {
                    r = n.map(function (e) {
                        return e.reference
                    })
                }
                function l(e) {
                    n.forEach(function (t) {
                        e ? t.enable() : t.disable()
                    })
                }
                function u(e) {
                    return n.map(function (t) {
                        var n = t.setProps
                        return (
                            (t.setProps = function (r) {
                                ;(n(r), t.reference === i && e.setProps(r))
                            }),
                            function () {
                                t.setProps = n
                            }
                        )
                    })
                }
                function d(e, t) {
                    var o = r.indexOf(t)
                    if (t !== i) {
                        i = t
                        var s = (a || []).concat(`content`).reduce(function (
                            e,
                            t,
                        ) {
                            return ((e[t] = n[o].props[t]), e)
                        }, {})
                        e.setProps(
                            Object.assign({}, s, {
                                getReferenceClientRect:
                                    typeof s.getReferenceClientRect ==
                                    `function`
                                        ? s.getReferenceClientRect
                                        : function () {
                                              return t.getBoundingClientRect()
                                          },
                            }),
                        )
                    }
                }
                ;(l(!1), c())
                var f = Z(
                        S(),
                        Object.assign({}, m(t, [`overrides`]), {
                            plugins: [
                                {
                                    fn: function () {
                                        return {
                                            onDestroy: function () {
                                                l(!0)
                                            },
                                            onHidden: function () {
                                                i = null
                                            },
                                            onClickOutside: function (e) {
                                                e.props.showOnCreate &&
                                                    !s &&
                                                    ((s = !0), (i = null))
                                            },
                                            onShow: function (e) {
                                                e.props.showOnCreate &&
                                                    !s &&
                                                    ((s = !0), d(e, r[0]))
                                            },
                                            onTrigger: function (e, t) {
                                                d(e, t.currentTarget)
                                            },
                                        }
                                    },
                                },
                            ].concat(t.plugins || []),
                            triggerTarget: r,
                            popperOptions: Object.assign({}, t.popperOptions, {
                                modifiers: [].concat(
                                    t.popperOptions?.modifiers || [],
                                    [ve],
                                ),
                            }),
                        }),
                    ),
                    p = f.show
                ;((f.show = function (e) {
                    if ((p(), !i && e == null)) return d(f, r[0])
                    if (!(i && e == null)) {
                        if (typeof e == `number`) return r[e] && d(f, r[e])
                        if (n.includes(e)) {
                            var t = e.reference
                            return d(f, t)
                        }
                        if (r.includes(e)) return d(f, e)
                    }
                }),
                    (f.showNext = function () {
                        var e = r[0]
                        if (!i) return f.show(0)
                        var t = r.indexOf(i)
                        f.show(r[t + 1] || e)
                    }),
                    (f.showPrevious = function () {
                        var e = r[r.length - 1]
                        if (!i) return f.show(e)
                        var t = r.indexOf(i),
                            n = r[t - 1] || e
                        f.show(n)
                    }))
                var h = f.setProps
                return (
                    (f.setProps = function (e) {
                        ;((a = e.overrides || a), h(e))
                    }),
                    (f.setInstances = function (e) {
                        ;(l(!0),
                            o.forEach(function (e) {
                                return e()
                            }),
                            (n = e),
                            l(!1),
                            c(),
                            u(f),
                            f.setProps({ triggerTarget: r }))
                    }),
                    (o = u(f)),
                    f
                )
            },
            be = { mouseover: `mouseenter`, focusin: `focus`, click: `click` }
        function xe(e, t) {
            U(
                !(t && t.target),
                [
                    'You must specity a `target` prop indicating a CSS selector string matching',
                    `the target elements that should receive a tippy.`,
                ].join(` `),
            )
            var n = [],
                r = [],
                i = !1,
                a = t.target,
                o = m(t, [`target`]),
                s = Object.assign({}, o, { trigger: `manual`, touch: !1 }),
                l = Object.assign({}, o, { showOnCreate: !0 }),
                u = Z(e, s),
                d = g(u)
            function f(e) {
                if (!(!e.target || i)) {
                    var n = e.target.closest(a)
                    if (n) {
                        var o =
                            n.getAttribute(`data-tippy-trigger`) ||
                            t.trigger ||
                            K.trigger
                        if (
                            !n._tippy &&
                            !(
                                e.type === `touchstart` &&
                                typeof l.touch == `boolean`
                            ) &&
                            !(
                                e.type !== `touchstart` &&
                                o.indexOf(be[e.type]) < 0
                            )
                        ) {
                            var s = Z(n, l)
                            s && (r = r.concat(s))
                        }
                    }
                }
            }
            function p(e, t, r, i) {
                ;(i === void 0 && (i = !1),
                    e.addEventListener(t, r, i),
                    n.push({ node: e, eventType: t, handler: r, options: i }))
            }
            function h(e) {
                var t = e.reference
                ;(p(t, `touchstart`, f, c),
                    p(t, `mouseover`, f),
                    p(t, `focusin`, f),
                    p(t, `click`, f))
            }
            function _() {
                ;(n.forEach(function (e) {
                    var t = e.node,
                        n = e.eventType,
                        r = e.handler,
                        i = e.options
                    t.removeEventListener(n, r, i)
                }),
                    (n = []))
            }
            function v(e) {
                var t = e.destroy,
                    n = e.enable,
                    a = e.disable
                ;((e.destroy = function (e) {
                    ;(e === void 0 && (e = !0),
                        e &&
                            r.forEach(function (e) {
                                e.destroy()
                            }),
                        (r = []),
                        _(),
                        t())
                }),
                    (e.enable = function () {
                        ;(n(),
                            r.forEach(function (e) {
                                return e.enable()
                            }),
                            (i = !1))
                    }),
                    (e.disable = function () {
                        ;(a(),
                            r.forEach(function (e) {
                                return e.disable()
                            }),
                            (i = !0))
                    }),
                    h(e))
            }
            return (d.forEach(v), u)
        }
        var Se = {
            name: `animateFill`,
            defaultValue: !1,
            fn: function (e) {
                if (!e.props.render?.$$tippy)
                    return (
                        U(
                            e.props.animateFill,
                            'The `animateFill` plugin requires the default render function.',
                        ),
                        {}
                    )
                var t = de(e.popper),
                    n = t.box,
                    r = t.content,
                    i = e.props.animateFill ? Ce() : null
                return {
                    onCreate: function () {
                        i &&
                            (n.insertBefore(i, n.firstElementChild),
                            n.setAttribute(`data-animatefill`, ``),
                            (n.style.overflow = `hidden`),
                            e.setProps({ arrow: !1, animation: `shift-away` }))
                    },
                    onMount: function () {
                        if (i) {
                            var e = n.style.transitionDuration,
                                t = Number(e.replace(`ms`, ``))
                            ;((r.style.transitionDelay =
                                Math.round(t / 10) + `ms`),
                                (i.style.transitionDuration = e),
                                k([i], `visible`))
                        }
                    },
                    onShow: function () {
                        i && (i.style.transitionDuration = `0ms`)
                    },
                    onHide: function () {
                        i && k([i], `hidden`)
                    },
                }
            },
        }
        function Ce() {
            var e = S()
            return ((e.className = a), k([e], `hidden`), e)
        }
        var we = { clientX: 0, clientY: 0 },
            Te = []
        function Ee(e) {
            we = { clientX: e.clientX, clientY: e.clientY }
        }
        function De(e) {
            e.addEventListener(`mousemove`, Ee)
        }
        function Oe(e) {
            e.removeEventListener(`mousemove`, Ee)
        }
        var ke = {
            name: `followCursor`,
            defaultValue: !1,
            fn: function (e) {
                var t = e.reference,
                    n = A(e.props.triggerTarget || t),
                    r = !1,
                    i = !1,
                    a = !0,
                    o = e.props
                function s() {
                    return (
                        e.props.followCursor === `initial` && e.state.isVisible
                    )
                }
                function c() {
                    n.addEventListener(`mousemove`, d)
                }
                function l() {
                    n.removeEventListener(`mousemove`, d)
                }
                function u() {
                    ;((r = !0),
                        e.setProps({ getReferenceClientRect: null }),
                        (r = !1))
                }
                function d(n) {
                    var r = n.target ? t.contains(n.target) : !0,
                        i = e.props.followCursor,
                        a = n.clientX,
                        o = n.clientY,
                        s = t.getBoundingClientRect(),
                        c = a - s.left,
                        l = o - s.top
                    ;(r || !e.props.interactive) &&
                        e.setProps({
                            getReferenceClientRect: function () {
                                var e = t.getBoundingClientRect(),
                                    n = a,
                                    r = o
                                i === `initial` &&
                                    ((n = e.left + c), (r = e.top + l))
                                var s = i === `horizontal` ? e.top : r,
                                    u = i === `vertical` ? e.right : n,
                                    d = i === `horizontal` ? e.bottom : r,
                                    f = i === `vertical` ? e.left : n
                                return {
                                    width: u - f,
                                    height: d - s,
                                    top: s,
                                    right: u,
                                    bottom: d,
                                    left: f,
                                }
                            },
                        })
                }
                function f() {
                    e.props.followCursor &&
                        (Te.push({ instance: e, doc: n }), De(n))
                }
                function p() {
                    ;((Te = Te.filter(function (t) {
                        return t.instance !== e
                    })),
                        Te.filter(function (e) {
                            return e.doc === n
                        }).length === 0 && Oe(n))
                }
                return {
                    onCreate: f,
                    onDestroy: p,
                    onBeforeUpdate: function () {
                        o = e.props
                    },
                    onAfterUpdate: function (t, n) {
                        var a = n.followCursor
                        r ||
                            (a !== void 0 &&
                                o.followCursor !== a &&
                                (p(),
                                a
                                    ? (f(),
                                      e.state.isMounted && !i && !s() && c())
                                    : (l(), u())))
                    },
                    onMount: function () {
                        e.props.followCursor &&
                            !i &&
                            ((a &&= (d(we), !1)), s() || c())
                    },
                    onTrigger: function (e, t) {
                        ;(T(t) &&
                            (we = { clientX: t.clientX, clientY: t.clientY }),
                            (i = t.type === `focus`))
                    },
                    onHidden: function () {
                        e.props.followCursor && (u(), l(), (a = !0))
                    },
                }
            },
        }
        function Ae(e, t) {
            return {
                popperOptions: Object.assign({}, e.popperOptions, {
                    modifiers: [].concat(
                        (e.popperOptions?.modifiers || []).filter(function (e) {
                            return e.name !== t.name
                        }),
                        [t],
                    ),
                }),
            }
        }
        var je = {
            name: `inlinePositioning`,
            defaultValue: !1,
            fn: function (e) {
                var t = e.reference
                function n() {
                    return !!e.props.inlinePositioning
                }
                var r,
                    i = -1,
                    a = !1,
                    o = {
                        name: `tippyInlinePositioning`,
                        enabled: !0,
                        phase: `afterWrite`,
                        fn: function (t) {
                            var i = t.state
                            n() &&
                                (r !== i.placement &&
                                    e.setProps({
                                        getReferenceClientRect: function () {
                                            return s(i.placement)
                                        },
                                    }),
                                (r = i.placement))
                        },
                    }
                function s(e) {
                    return Me(
                        y(e),
                        t.getBoundingClientRect(),
                        b(t.getClientRects()),
                        i,
                    )
                }
                function c(t) {
                    ;((a = !0), e.setProps(t), (a = !1))
                }
                function l() {
                    a || c(Ae(e.props, o))
                }
                return {
                    onCreate: l,
                    onAfterUpdate: l,
                    onTrigger: function (t, n) {
                        if (T(n)) {
                            var r = b(e.reference.getClientRects()),
                                a = r.find(function (e) {
                                    return (
                                        e.left - 2 <= n.clientX &&
                                        e.right + 2 >= n.clientX &&
                                        e.top - 2 <= n.clientY &&
                                        e.bottom + 2 >= n.clientY
                                    )
                                })
                            i = r.indexOf(a)
                        }
                    },
                    onUntrigger: function () {
                        i = -1
                    },
                }
            },
        }
        function Me(e, t, n, r) {
            if (n.length < 2 || e === null) return t
            if (n.length === 2 && r >= 0 && n[0].left > n[1].right)
                return n[r] || t
            switch (e) {
                case `top`:
                case `bottom`:
                    var i = n[0],
                        a = n[n.length - 1],
                        o = e === `top`,
                        s = i.top,
                        c = a.bottom,
                        l = o ? i.left : a.left,
                        u = o ? i.right : a.right
                    return {
                        top: s,
                        bottom: c,
                        left: l,
                        right: u,
                        width: u - l,
                        height: c - s,
                    }
                case `left`:
                case `right`:
                    var d = Math.min.apply(
                            Math,
                            n.map(function (e) {
                                return e.left
                            }),
                        ),
                        f = Math.max.apply(
                            Math,
                            n.map(function (e) {
                                return e.right
                            }),
                        ),
                        p = n.filter(function (t) {
                            return e === `left` ? t.left === d : t.right === f
                        }),
                        m = p[0].top,
                        h = p[p.length - 1].bottom,
                        g = d,
                        _ = f
                    return {
                        top: m,
                        bottom: h,
                        left: g,
                        right: _,
                        width: _ - g,
                        height: h - m,
                    }
                default:
                    return t
            }
        }
        var Ne = {
            name: `sticky`,
            defaultValue: !1,
            fn: function (e) {
                var t = e.reference,
                    n = e.popper
                function r() {
                    return e.popperInstance
                        ? e.popperInstance.state.elements.reference
                        : t
                }
                function i(t) {
                    return e.props.sticky === !0 || e.props.sticky === t
                }
                var a = null,
                    o = null
                function s() {
                    var t = i(`reference`) ? r().getBoundingClientRect() : null,
                        c = i(`popper`) ? n.getBoundingClientRect() : null
                    ;(((t && Pe(a, t)) || (c && Pe(o, c))) &&
                        e.popperInstance &&
                        e.popperInstance.update(),
                        (a = t),
                        (o = c),
                        e.state.isMounted && requestAnimationFrame(s))
                }
                return {
                    onMount: function () {
                        e.props.sticky && s()
                    },
                }
            },
        }
        function Pe(e, t) {
            return e && t
                ? e.top !== t.top ||
                      e.right !== t.right ||
                      e.bottom !== t.bottom ||
                      e.left !== t.left
                : !0
        }
        ;(Z.setDefaultProps({ render: fe }),
            (e.animateFill = Se),
            (e.createSingleton = ye),
            (e.default = Z),
            (e.delegate = xe),
            (e.followCursor = ke),
            (e.hideAll = _e),
            (e.inlinePositioning = je),
            (e.roundArrow = n),
            (e.sticky = Ne))
    }),
    ut = st(lt()),
    dt = st(lt()),
    ft = (e) => {
        let t = { plugins: [] },
            n = (t) => e[e.indexOf(t) + 1]
        if (
            (e.includes(`animation`) && (t.animation = n(`animation`)),
            e.includes(`duration`) && (t.duration = parseInt(n(`duration`))),
            e.includes(`delay`))
        ) {
            let e = n(`delay`)
            t.delay = e.includes(`-`)
                ? e.split(`-`).map((e) => parseInt(e))
                : parseInt(e)
        }
        if (e.includes(`cursor`)) {
            t.plugins.push(dt.followCursor)
            let e = n(`cursor`)
            ;[`x`, `initial`].includes(e)
                ? (t.followCursor = e === `x` ? `horizontal` : `initial`)
                : (t.followCursor = !0)
        }
        ;(e.includes(`on`) && (t.trigger = n(`on`)),
            e.includes(`arrowless`) && (t.arrow = !1),
            e.includes(`html`) && (t.allowHTML = !0),
            e.includes(`interactive`) && (t.interactive = !0),
            e.includes(`border`) &&
                t.interactive &&
                (t.interactiveBorder = parseInt(n(`border`))),
            e.includes(`debounce`) &&
                t.interactive &&
                (t.interactiveDebounce = parseInt(n(`debounce`))),
            e.includes(`max-width`) && (t.maxWidth = parseInt(n(`max-width`))),
            e.includes(`theme`) && (t.theme = n(`theme`)),
            e.includes(`placement`) && (t.placement = n(`placement`)))
        let r = {}
        return (
            e.includes(`no-flip`) &&
                ((r.modifiers ||= []),
                r.modifiers.push({ name: `flip`, enabled: !1 })),
            (t.popperOptions = r),
            t
        )
    }
function pt(e) {
    ;(e.magic(`tooltip`, (e) => (t, n = {}) => {
        let r = n.timeout
        delete n.timeout
        let i = (0, ut.default)(e, { content: t, trigger: `manual`, ...n })
        ;(i.show(),
            setTimeout(() => {
                ;(i.hide(), setTimeout(() => i.destroy(), n.duration || 300))
            }, r || 2e3))
    }),
        e.directive(
            `tooltip`,
            (
                e,
                { modifiers: t, expression: n },
                { evaluateLater: r, effect: i, cleanup: a },
            ) => {
                let o = t.length > 0 ? ft(t) : {}
                ;((e.__x_tippy ||= (0, ut.default)(e, o)),
                    a(() => {
                        e.__x_tippy &&
                            (e.__x_tippy.destroy(), delete e.__x_tippy)
                    }))
                let s = () => e.__x_tippy.enable(),
                    c = () => e.__x_tippy.disable(),
                    l = (t) => {
                        t ? (s(), e.__x_tippy.setContent(t)) : c()
                    }
                if (t.includes(`raw`)) l(n)
                else {
                    let t = r(n)
                    i(() => {
                        t((t) => {
                            typeof t == `object`
                                ? (e.__x_tippy.setProps(t), s())
                                : l(t)
                        })
                    })
                }
            },
        ))
}
pt.defaultProps = (e) => (ut.default.setDefaultProps(e), pt)
var mt = pt
;(function () {
    let e = `.lightbox`,
        t = `data-lightbox`,
        n = `default`,
        r = `data-group`,
        i = `data-title`,
        a = `data-type`
    document.addEventListener(`livewire:init`, () => {
        let o = {}
        function s() {
            ;((o = {}),
                document.querySelectorAll(e).forEach((e) => {
                    let s = e.getAttribute(r) || n
                    ;(o[s] || (o[s] = []),
                        o[s].push({
                            type: e.getAttribute(a),
                            url: e.getAttribute(t),
                            title: e.getAttribute(i) || e.getAttribute(`alt`),
                        }))
                }))
        }
        function c() {
            let e = document.getElementsByClassName(`swiper`)
            for (let t = 0; t < e.length; t++)
                e[t].dispatchEvent(new Event(`disable-carousel`))
        }
        function l(e) {
            ;(s(),
                window.dispatchEvent(
                    new CustomEvent(`lightbox`, {
                        detail: {
                            group: e.getAttribute(r) || n,
                            type: e.getAttribute(a),
                            url: e.getAttribute(t),
                            title: e.getAttribute(i) || e.getAttribute(`alt`),
                        },
                    }),
                ),
                c())
        }
        ;(document.addEventListener(`click`, (t) => {
            let n = t.target.closest(e)
            n && (t.preventDefault(), l(n))
        }),
            document.addEventListener(`keydown`, (t) => {
                if (![`Enter`, ` `].includes(t.key)) return
                let n = t.target.closest(e)
                n && (t.preventDefault(), l(n))
            }),
            Alpine.data(`lightbox`, () => ({
                currentIndex: null,
                currentGroup: null,
                currentType: null,
                currentTitle: ``,
                currentUrl: ``,
                previousFocus: null,
                load(e, t) {
                    if (!o[e] || !o[e][t]) return !1
                    ;((this.currentGroup = e),
                        (this.currentIndex = t),
                        (this.currentTitle = o[e][t].title || ``),
                        (this.currentType = o[e][t].type || `image`),
                        (this.currentUrl = o[e][t].url || ``),
                        this.$nextTick(() => {
                            this.$refs.lightboxDialog?.focus()
                        }))
                },
                close: function () {
                    ;((this.currentGroup = null),
                        (this.currentIndex = null),
                        (this.currentTitle = ``),
                        (this.currentType = null),
                        (this.currentUrl = ``),
                        this.previousFocus?.focus?.(),
                        (this.previousFocus = null))
                    let e = document.getElementsByClassName(`swiper`)
                    for (let t = 0; t < e.length; t++)
                        e[t].dispatchEvent(new Event(`enable-carousel`))
                },
                loadPrevious() {
                    if (!o[this.currentGroup]) return !1
                    let e = this.currentIndex - 1
                    ;(e === -1 && (e = o[this.currentGroup].length - 1),
                        this.load(this.currentGroup, e))
                },
                loadNext() {
                    if (!o[this.currentGroup]) return !1
                    let e = this.currentIndex + 1
                    ;(e === o[this.currentGroup].length && (e = 0),
                        this.load(this.currentGroup, e))
                },
                lightbox(e) {
                    if ((s(), !o[e.detail.group])) return !1
                    let t = o[e.detail.group].findIndex(
                        (t) => t.url === e.detail.url,
                    )
                    t !== -1 &&
                        ((this.previousFocus = document.activeElement),
                        this.load(e.detail.group, t))
                },
                total() {
                    return o[this.currentGroup]
                        ? o[this.currentGroup].length
                        : 0
                },
            })))
    })
})()
function ht(e) {
    return (
        typeof e == `object` &&
        !!e &&
        `constructor` in e &&
        e.constructor === Object
    )
}
function gt(e = {}, t = {}) {
    let n = [`__proto__`, `constructor`, `prototype`]
    Object.keys(t)
        .filter((e) => n.indexOf(e) < 0)
        .forEach((n) => {
            e[n] === void 0
                ? (e[n] = t[n])
                : ht(t[n]) &&
                  ht(e[n]) &&
                  Object.keys(t[n]).length > 0 &&
                  gt(e[n], t[n])
        })
}
var _t = {
    body: {},
    addEventListener() {},
    removeEventListener() {},
    activeElement: { blur() {}, nodeName: `` },
    querySelector() {
        return null
    },
    querySelectorAll() {
        return []
    },
    getElementById() {
        return null
    },
    createEvent() {
        return { initEvent() {} }
    },
    createElement() {
        return {
            children: [],
            childNodes: [],
            style: {},
            setAttribute() {},
            getElementsByTagName() {
                return []
            },
        }
    },
    createElementNS() {
        return {}
    },
    importNode() {
        return null
    },
    location: {
        hash: ``,
        host: ``,
        hostname: ``,
        href: ``,
        origin: ``,
        pathname: ``,
        protocol: ``,
        search: ``,
    },
}
function vt() {
    let e = typeof document < `u` ? document : {}
    return (gt(e, _t), e)
}
var yt = {
    document: _t,
    navigator: { userAgent: `` },
    location: {
        hash: ``,
        host: ``,
        hostname: ``,
        href: ``,
        origin: ``,
        pathname: ``,
        protocol: ``,
        search: ``,
    },
    history: { replaceState() {}, pushState() {}, go() {}, back() {} },
    CustomEvent: function () {
        return this
    },
    addEventListener() {},
    removeEventListener() {},
    getComputedStyle() {
        return {
            getPropertyValue() {
                return ``
            },
        }
    },
    Image() {},
    Date() {},
    screen: {},
    setTimeout() {},
    clearTimeout() {},
    matchMedia() {
        return {}
    },
    requestAnimationFrame(e) {
        return typeof setTimeout > `u` ? (e(), null) : setTimeout(e, 0)
    },
    cancelAnimationFrame(e) {
        typeof setTimeout > `u` || clearTimeout(e)
    },
}
function Q() {
    let e = typeof window < `u` ? window : {}
    return (gt(e, yt), e)
}
function bt(e = ``) {
    return e
        .trim()
        .split(` `)
        .filter((e) => !!e.trim())
}
function xt(e) {
    let t = e
    Object.keys(t).forEach((e) => {
        try {
            t[e] = null
        } catch {}
        try {
            delete t[e]
        } catch {}
    })
}
function St(e, t = 0) {
    return setTimeout(e, t)
}
function Ct() {
    return Date.now()
}
function wt(e) {
    let t = Q(),
        n
    return (
        t.getComputedStyle && (n = t.getComputedStyle(e, null)),
        !n && e.currentStyle && (n = e.currentStyle),
        (n ||= e.style),
        n
    )
}
function Tt(e, t = `x`) {
    let n = Q(),
        r,
        i,
        a,
        o = wt(e)
    return (
        n.WebKitCSSMatrix
            ? ((i = o.transform || o.webkitTransform),
              i.split(`,`).length > 6 &&
                  (i = i
                      .split(`, `)
                      .map((e) => e.replace(`,`, `.`))
                      .join(`, `)),
              (a = new n.WebKitCSSMatrix(i === `none` ? `` : i)))
            : ((a =
                  o.MozTransform ||
                  o.OTransform ||
                  o.MsTransform ||
                  o.msTransform ||
                  o.transform ||
                  o
                      .getPropertyValue(`transform`)
                      .replace(`translate(`, `matrix(1, 0, 0, 1,`)),
              (r = a.toString().split(`,`))),
        t === `x` &&
            (i = n.WebKitCSSMatrix
                ? a.m41
                : r.length === 16
                  ? parseFloat(r[12])
                  : parseFloat(r[4])),
        t === `y` &&
            (i = n.WebKitCSSMatrix
                ? a.m42
                : r.length === 16
                  ? parseFloat(r[13])
                  : parseFloat(r[5])),
        i || 0
    )
}
function Et(e) {
    return (
        typeof e == `object` &&
        !!e &&
        e.constructor &&
        Object.prototype.toString.call(e).slice(8, -1) === `Object`
    )
}
function Dt(e) {
    return typeof window < `u` && window.HTMLElement !== void 0
        ? e instanceof HTMLElement
        : e && (e.nodeType === 1 || e.nodeType === 11)
}
function Ot(...e) {
    let t = Object(e[0])
    for (let n = 1; n < e.length; n += 1) {
        let r = e[n]
        if (r != null && !Dt(r)) {
            let e = Object.keys(Object(r)).filter(
                (e) =>
                    e !== `__proto__` &&
                    e !== `constructor` &&
                    e !== `prototype`,
            )
            for (let n = 0, i = e.length; n < i; n += 1) {
                let i = e[n],
                    a = Object.getOwnPropertyDescriptor(r, i)
                a !== void 0 &&
                    a.enumerable &&
                    (Et(t[i]) && Et(r[i])
                        ? r[i].__swiper__
                            ? (t[i] = r[i])
                            : Ot(t[i], r[i])
                        : !Et(t[i]) && Et(r[i])
                          ? ((t[i] = {}),
                            r[i].__swiper__ ? (t[i] = r[i]) : Ot(t[i], r[i]))
                          : (t[i] = r[i]))
            }
        }
    }
    return t
}
function kt(e, t, n) {
    e.style.setProperty(t, n)
}
function At({ swiper: e, targetPosition: t, side: n }) {
    let r = Q(),
        i = -e.translate,
        a = null,
        o,
        s = e.params.speed
    ;((e.wrapperEl.style.scrollSnapType = `none`),
        r.cancelAnimationFrame(e.cssModeFrameID))
    let c = t > i ? `next` : `prev`,
        l = (e, t) => (c === `next` && e >= t) || (c === `prev` && e <= t),
        u = () => {
            ;((o = new Date().getTime()), a === null && (a = o))
            let c = Math.max(Math.min((o - a) / s, 1), 0),
                d = i + (0.5 - Math.cos(c * Math.PI) / 2) * (t - i)
            if (
                (l(d, t) && (d = t), e.wrapperEl.scrollTo({ [n]: d }), l(d, t))
            ) {
                ;((e.wrapperEl.style.overflow = `hidden`),
                    (e.wrapperEl.style.scrollSnapType = ``),
                    setTimeout(() => {
                        ;((e.wrapperEl.style.overflow = ``),
                            e.wrapperEl.scrollTo({ [n]: d }))
                    }),
                    r.cancelAnimationFrame(e.cssModeFrameID))
                return
            }
            e.cssModeFrameID = r.requestAnimationFrame(u)
        }
    u()
}
function jt(e) {
    return (
        e.querySelector(`.swiper-slide-transform`) ||
        (e.shadowRoot &&
            e.shadowRoot.querySelector(`.swiper-slide-transform`)) ||
        e
    )
}
function Mt(e, t = ``) {
    let n = Q(),
        r = [...e.children]
    return (
        n.HTMLSlotElement &&
            e instanceof HTMLSlotElement &&
            r.push(...e.assignedElements()),
        t ? r.filter((e) => e.matches(t)) : r
    )
}
function Nt(e, t) {
    let n = [t]
    for (; n.length > 0; ) {
        let t = n.shift()
        if (e === t) return !0
        n.push(
            ...t.children,
            ...(t.shadowRoot ? t.shadowRoot.children : []),
            ...(t.assignedElements ? t.assignedElements() : []),
        )
    }
}
function Pt(e, t) {
    let n = Q(),
        r = t.contains(e)
    return (
        !r &&
            n.HTMLSlotElement &&
            t instanceof HTMLSlotElement &&
            ((r = [...t.assignedElements()].includes(e)), (r ||= Nt(e, t))),
        r
    )
}
function Ft(e) {
    try {
        console.warn(e)
        return
    } catch {}
}
function It(e, t = []) {
    let n = document.createElement(e)
    return (n.classList.add(...(Array.isArray(t) ? t : bt(t))), n)
}
function Lt(e, t) {
    let n = []
    for (; e.previousElementSibling; ) {
        let r = e.previousElementSibling
        ;(t ? r.matches(t) && n.push(r) : n.push(r), (e = r))
    }
    return n
}
function Rt(e, t) {
    let n = []
    for (; e.nextElementSibling; ) {
        let r = e.nextElementSibling
        ;(t ? r.matches(t) && n.push(r) : n.push(r), (e = r))
    }
    return n
}
function zt(e, t) {
    return Q().getComputedStyle(e, null).getPropertyValue(t)
}
function Bt(e) {
    let t = e,
        n
    if (t) {
        for (n = 0; (t = t.previousSibling) !== null; )
            t.nodeType === 1 && (n += 1)
        return n
    }
}
function Vt(e, t) {
    let n = [],
        r = e.parentElement
    for (; r; )
        (t ? r.matches(t) && n.push(r) : n.push(r), (r = r.parentElement))
    return n
}
function Ht(e, t) {
    function n(r) {
        r.target === e &&
            (t.call(e, r), e.removeEventListener(`transitionend`, n))
    }
    t && e.addEventListener(`transitionend`, n)
}
function Ut(e, t, n) {
    let r = Q()
    return n
        ? e[t === `width` ? `offsetWidth` : `offsetHeight`] +
              parseFloat(
                  r
                      .getComputedStyle(e, null)
                      .getPropertyValue(
                          t === `width` ? `margin-right` : `margin-top`,
                      ),
              ) +
              parseFloat(
                  r
                      .getComputedStyle(e, null)
                      .getPropertyValue(
                          t === `width` ? `margin-left` : `margin-bottom`,
                      ),
              )
        : e.offsetWidth
}
function $(e) {
    return (Array.isArray(e) ? e : [e]).filter((e) => !!e)
}
function Wt(e, t = ``) {
    typeof trustedTypes < `u`
        ? (e.innerHTML = trustedTypes
              .createPolicy(`html`, { createHTML: (e) => e })
              .createHTML(t))
        : (e.innerHTML = t)
}
var Gt
function Kt() {
    let e = Q(),
        t = vt()
    return {
        smoothScroll:
            t.documentElement &&
            t.documentElement.style &&
            `scrollBehavior` in t.documentElement.style,
        touch: !!(
            `ontouchstart` in e ||
            (e.DocumentTouch && t instanceof e.DocumentTouch)
        ),
    }
}
function qt() {
    return ((Gt ||= Kt()), Gt)
}
var Jt
function Yt({ userAgent: e } = {}) {
    let t = qt(),
        n = Q(),
        r = n.navigator.platform,
        i = e || n.navigator.userAgent,
        a = { ios: !1, android: !1 },
        o = n.screen.width,
        s = n.screen.height,
        c = i.match(/(Android);?[\s\/]+([\d.]+)?/),
        l = i.match(/(iPad)(?!\1).*OS\s([\d_]+)/),
        u = i.match(/(iPod)(.*OS\s([\d_]+))?/),
        d = !l && i.match(/(iPhone\sOS|iOS)\s([\d_]+)/),
        f = r === `Win32`,
        p = r === `MacIntel`
    return (
        !l &&
            p &&
            t.touch &&
            [
                `1024x1366`,
                `1366x1024`,
                `834x1194`,
                `1194x834`,
                `834x1112`,
                `1112x834`,
                `768x1024`,
                `1024x768`,
                `820x1180`,
                `1180x820`,
                `810x1080`,
                `1080x810`,
            ].indexOf(`${o}x${s}`) >= 0 &&
            ((l = i.match(/(Version)\/([\d.]+)/)),
            (l ||= [0, 1, `13_0_0`]),
            (p = !1)),
        c && !f && ((a.os = `android`), (a.android = !0)),
        (l || d || u) && ((a.os = `ios`), (a.ios = !0)),
        a
    )
}
function Xt(e = {}) {
    return ((Jt ||= Yt(e)), Jt)
}
var Zt
function Qt() {
    let e = Q(),
        t = Xt(),
        n = !1
    function r() {
        let t = e.navigator.userAgent.toLowerCase()
        return (
            t.indexOf(`safari`) >= 0 &&
            t.indexOf(`chrome`) < 0 &&
            t.indexOf(`android`) < 0
        )
    }
    if (r()) {
        let t = String(e.navigator.userAgent)
        if (t.includes(`Version/`)) {
            let [e, r] = t
                .split(`Version/`)[1]
                .split(` `)[0]
                .split(`.`)
                .map((e) => Number(e))
            n = e < 16 || (e === 16 && r < 2)
        }
    }
    let i = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(
            e.navigator.userAgent,
        ),
        a = r(),
        o = a || (i && t.ios)
    return {
        isSafari: n || a,
        needPerspectiveFix: n,
        need3dFix: o,
        isWebView: i,
    }
}
function $t() {
    return ((Zt ||= Qt()), Zt)
}
function en({ swiper: e, on: t, emit: n }) {
    let r = Q(),
        i = null,
        a = null,
        o = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                (n(`beforeResize`), n(`resize`))
        },
        s = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                ((i = new ResizeObserver((t) => {
                    a = r.requestAnimationFrame(() => {
                        let { width: n, height: r } = e,
                            i = n,
                            a = r
                        ;(t.forEach(
                            ({
                                contentBoxSize: t,
                                contentRect: n,
                                target: r,
                            }) => {
                                ;(r && r !== e.el) ||
                                    ((i = n ? n.width : (t[0] || t).inlineSize),
                                    (a = n ? n.height : (t[0] || t).widgetSize))
                            },
                        ),
                            (i !== n || a !== r) && o())
                    })
                })),
                i.observe(e.el))
        },
        c = () => {
            ;(a && r.cancelAnimationFrame(a),
                i && i.unobserve && e.el && (i.unobserve(e.el), (i = null)))
        },
        l = () => {
            !e || e.destroyed || !e.initialized || n(`orientationchange`)
        }
    ;(t(`init`, () => {
        if (e.params.resizeObserver && r.ResizeObserver !== void 0) {
            s()
            return
        }
        ;(r.addEventListener(`resize`, o),
            r.addEventListener(`orientationchange`, l))
    }),
        t(`destroy`, () => {
            ;(c(),
                r.removeEventListener(`resize`, o),
                r.removeEventListener(`orientationchange`, l))
        }))
}
function tn({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = [],
        a = Q(),
        o = (t, n = {}) => {
            let o = new (a.MutationObserver || a.WebkitMutationObserver)(
                (t) => {
                    if (e.__preventObserver__) return
                    if (t.length === 1) {
                        r(`observerUpdate`, t[0])
                        return
                    }
                    let n = function () {
                        r(`observerUpdate`, t[0])
                    }
                    a.requestAnimationFrame
                        ? a.requestAnimationFrame(n)
                        : a.setTimeout(n, 0)
                },
            )
            ;(o.observe(t, {
                attributes: n.attributes === void 0 ? !0 : n.attributes,
                childList:
                    e.isElement || (n.childList === void 0 ? !0 : n).childList,
                characterData:
                    n.characterData === void 0 ? !0 : n.characterData,
            }),
                i.push(o))
        }
    ;(t({ observer: !1, observeParents: !1, observeSlideChildren: !1 }),
        n(`init`, () => {
            if (e.params.observer) {
                if (e.params.observeParents) {
                    let t = Vt(e.hostEl)
                    for (let e = 0; e < t.length; e += 1) o(t[e])
                }
                ;(o(e.hostEl, { childList: e.params.observeSlideChildren }),
                    o(e.wrapperEl, { attributes: !1 }))
            }
        }),
        n(`destroy`, () => {
            ;(i.forEach((e) => {
                e.disconnect()
            }),
                i.splice(0, i.length))
        }))
}
var nn = {
    on(e, t, n) {
        let r = this
        if (!r.eventsListeners || r.destroyed || typeof t != `function`)
            return r
        let i = n ? `unshift` : `push`
        return (
            e.split(` `).forEach((e) => {
                ;(r.eventsListeners[e] || (r.eventsListeners[e] = []),
                    r.eventsListeners[e][i](t))
            }),
            r
        )
    },
    once(e, t, n) {
        let r = this
        if (!r.eventsListeners || r.destroyed || typeof t != `function`)
            return r
        function i(...n) {
            ;(r.off(e, i),
                i.__emitterProxy && delete i.__emitterProxy,
                t.apply(r, n))
        }
        return ((i.__emitterProxy = t), r.on(e, i, n))
    },
    onAny(e, t) {
        let n = this
        if (!n.eventsListeners || n.destroyed || typeof e != `function`)
            return n
        let r = t ? `unshift` : `push`
        return (
            n.eventsAnyListeners.indexOf(e) < 0 && n.eventsAnyListeners[r](e),
            n
        )
    },
    offAny(e) {
        let t = this
        if (!t.eventsListeners || t.destroyed || !t.eventsAnyListeners) return t
        let n = t.eventsAnyListeners.indexOf(e)
        return (n >= 0 && t.eventsAnyListeners.splice(n, 1), t)
    },
    off(e, t) {
        let n = this
        return (
            !n.eventsListeners ||
                n.destroyed ||
                !n.eventsListeners ||
                e.split(` `).forEach((e) => {
                    t === void 0
                        ? (n.eventsListeners[e] = [])
                        : n.eventsListeners[e] &&
                          n.eventsListeners[e].forEach((r, i) => {
                              ;(r === t ||
                                  (r.__emitterProxy &&
                                      r.__emitterProxy === t)) &&
                                  n.eventsListeners[e].splice(i, 1)
                          })
                }),
            n
        )
    },
    emit(...e) {
        let t = this
        if (!t.eventsListeners || t.destroyed || !t.eventsListeners) return t
        let n, r, i
        return (
            typeof e[0] == `string` || Array.isArray(e[0])
                ? ((n = e[0]), (r = e.slice(1, e.length)), (i = t))
                : ((n = e[0].events), (r = e[0].data), (i = e[0].context || t)),
            r.unshift(i),
            (Array.isArray(n) ? n : n.split(` `)).forEach((e) => {
                ;(t.eventsAnyListeners &&
                    t.eventsAnyListeners.length &&
                    t.eventsAnyListeners.forEach((t) => {
                        t.apply(i, [e, ...r])
                    }),
                    t.eventsListeners &&
                        t.eventsListeners[e] &&
                        t.eventsListeners[e].forEach((e) => {
                            e.apply(i, r)
                        }))
            }),
            t
        )
    },
}
function rn() {
    let e = this,
        t,
        n,
        r = e.el
    ;((t =
        e.params.width !== void 0 && e.params.width !== null
            ? e.params.width
            : r.clientWidth),
        (n =
            e.params.height !== void 0 && e.params.height !== null
                ? e.params.height
                : r.clientHeight),
        !((t === 0 && e.isHorizontal()) || (n === 0 && e.isVertical())) &&
            ((t =
                t -
                parseInt(zt(r, `padding-left`) || 0, 10) -
                parseInt(zt(r, `padding-right`) || 0, 10)),
            (n =
                n -
                parseInt(zt(r, `padding-top`) || 0, 10) -
                parseInt(zt(r, `padding-bottom`) || 0, 10)),
            Number.isNaN(t) && (t = 0),
            Number.isNaN(n) && (n = 0),
            Object.assign(e, {
                width: t,
                height: n,
                size: e.isHorizontal() ? t : n,
            })))
}
function an() {
    let e = this
    function t(t, n) {
        return parseFloat(t.getPropertyValue(e.getDirectionLabel(n)) || 0)
    }
    let n = e.params,
        { wrapperEl: r, slidesEl: i, rtlTranslate: a, wrongRTL: o } = e,
        s = e.virtual && n.virtual.enabled,
        c = s ? e.virtual.slides.length : e.slides.length,
        l = Mt(i, `.${e.params.slideClass}, swiper-slide`),
        u = s ? e.virtual.slides.length : l.length,
        d = [],
        f = [],
        p = [],
        m = n.slidesOffsetBefore
    typeof m == `function` && (m = n.slidesOffsetBefore.call(e))
    let h = n.slidesOffsetAfter
    typeof h == `function` && (h = n.slidesOffsetAfter.call(e))
    let g = e.snapGrid.length,
        _ = e.slidesGrid.length,
        v = e.size - m - h,
        y = n.spaceBetween,
        b = -m,
        x = 0,
        S = 0
    if (v === void 0) return
    ;(typeof y == `string` && y.indexOf(`%`) >= 0
        ? (y = (parseFloat(y.replace(`%`, ``)) / 100) * v)
        : typeof y == `string` && (y = parseFloat(y)),
        (e.virtualSize = -y - m - h),
        l.forEach((e) => {
            ;(a ? (e.style.marginLeft = ``) : (e.style.marginRight = ``),
                (e.style.marginBottom = ``),
                (e.style.marginTop = ``))
        }),
        n.centeredSlides &&
            n.cssMode &&
            (kt(r, `--swiper-centered-offset-before`, ``),
            kt(r, `--swiper-centered-offset-after`, ``)),
        n.cssMode &&
            (kt(r, `--swiper-slides-offset-before`, `${m}px`),
            kt(r, `--swiper-slides-offset-after`, `${h}px`)))
    let C = n.grid && n.grid.rows > 1 && e.grid
    C ? e.grid.initSlides(l) : e.grid && e.grid.unsetSlides()
    let w,
        T =
            n.slidesPerView === `auto` &&
            n.breakpoints &&
            Object.keys(n.breakpoints).filter(
                (e) => n.breakpoints[e].slidesPerView !== void 0,
            ).length > 0
    for (let r = 0; r < u; r += 1) {
        w = 0
        let i = l[r]
        if (
            !(
                i &&
                (C && e.grid.updateSlide(r, i, l), zt(i, `display`) === `none`)
            )
        ) {
            if (s && n.slidesPerView === `auto`)
                (n.virtual.slidesPerViewAutoSlideSize &&
                    (w = n.virtual.slidesPerViewAutoSlideSize),
                    w &&
                        i &&
                        (n.roundLengths && (w = Math.floor(w)),
                        (i.style[e.getDirectionLabel(`width`)] = `${w}px`)))
            else if (n.slidesPerView === `auto`) {
                T && (i.style[e.getDirectionLabel(`width`)] = ``)
                let r = getComputedStyle(i),
                    a = i.style.transform,
                    o = i.style.webkitTransform
                if (
                    (a && (i.style.transform = `none`),
                    o && (i.style.webkitTransform = `none`),
                    n.roundLengths)
                )
                    w = e.isHorizontal()
                        ? Ut(i, `width`, !0)
                        : Ut(i, `height`, !0)
                else {
                    let e = t(r, `width`),
                        n = t(r, `padding-left`),
                        a = t(r, `padding-right`),
                        o = t(r, `margin-left`),
                        s = t(r, `margin-right`),
                        c = r.getPropertyValue(`box-sizing`)
                    if (c && c === `border-box`) w = e + o + s
                    else {
                        let { clientWidth: t, offsetWidth: r } = i
                        w = e + n + a + o + s + (r - t)
                    }
                }
                ;(a && (i.style.transform = a),
                    o && (i.style.webkitTransform = o),
                    n.roundLengths && (w = Math.floor(w)))
            } else
                ((w = (v - (n.slidesPerView - 1) * y) / n.slidesPerView),
                    n.roundLengths && (w = Math.floor(w)),
                    i && (i.style[e.getDirectionLabel(`width`)] = `${w}px`))
            ;(i && (i.swiperSlideSize = w),
                p.push(w),
                n.centeredSlides
                    ? ((b = b + w / 2 + x / 2 + y),
                      x === 0 && r !== 0 && (b = b - v / 2 - y),
                      r === 0 && (b = b - v / 2 - y),
                      Math.abs(b) < 1 / 1e3 && (b = 0),
                      n.roundLengths && (b = Math.floor(b)),
                      S % n.slidesPerGroup === 0 && d.push(b),
                      f.push(b))
                    : (n.roundLengths && (b = Math.floor(b)),
                      (S - Math.min(e.params.slidesPerGroupSkip, S)) %
                          e.params.slidesPerGroup ===
                          0 && d.push(b),
                      f.push(b),
                      (b = b + w + y)),
                (e.virtualSize += w + y),
                (x = w),
                (S += 1))
        }
    }
    if (
        ((e.virtualSize = Math.max(e.virtualSize, v) + h),
        a &&
            o &&
            (n.effect === `slide` || n.effect === `coverflow`) &&
            (r.style.width = `${e.virtualSize + y}px`),
        n.setWrapperSize &&
            (r.style[e.getDirectionLabel(`width`)] = `${e.virtualSize + y}px`),
        C && e.grid.updateWrapperSize(w, d),
        !n.centeredSlides)
    ) {
        let t = n.slidesPerView !== `auto` && n.slidesPerView % 1 != 0,
            r =
                n.snapToSlideEdge &&
                !n.loop &&
                (n.slidesPerView === `auto` || t),
            i = d.length
        if (r) {
            let e
            if (n.slidesPerView === `auto`) {
                e = 1
                let t = 0
                for (
                    let n = p.length - 1;
                    n >= 0 &&
                    ((t += p[n] + (n < p.length - 1 ? y : 0)), t <= v);
                    --n
                )
                    e = p.length - n
            } else e = Math.floor(n.slidesPerView)
            i = Math.max(u - e, 0)
        }
        let a = []
        for (let t = 0; t < d.length; t += 1) {
            let o = d[t]
            ;(n.roundLengths && (o = Math.floor(o)),
                r
                    ? t <= i && a.push(o)
                    : d[t] <= e.virtualSize - v && a.push(o))
        }
        ;((d = a),
            Math.floor(e.virtualSize - v) - Math.floor(d[d.length - 1]) > 1 &&
                (r || d.push(e.virtualSize - v)))
    }
    if (s && n.loop) {
        let t = p[0] + y
        if (n.slidesPerGroup > 1) {
            let r = Math.ceil(
                    (e.virtual.slidesBefore + e.virtual.slidesAfter) /
                        n.slidesPerGroup,
                ),
                i = t * n.slidesPerGroup
            for (let e = 0; e < r; e += 1) d.push(d[d.length - 1] + i)
        }
        for (
            let r = 0;
            r < e.virtual.slidesBefore + e.virtual.slidesAfter;
            r += 1
        )
            (n.slidesPerGroup === 1 && d.push(d[d.length - 1] + t),
                f.push(f[f.length - 1] + t),
                (e.virtualSize += t))
    }
    if ((d.length === 0 && (d = [0]), y !== 0)) {
        let t =
            e.isHorizontal() && a
                ? `marginLeft`
                : e.getDirectionLabel(`marginRight`)
        l.filter((e, t) =>
            !n.cssMode || n.loop ? !0 : t !== l.length - 1,
        ).forEach((e) => {
            e.style[t] = `${y}px`
        })
    }
    if (n.centeredSlides && n.centeredSlidesBounds) {
        let e = 0
        ;(p.forEach((t) => {
            e += t + (y || 0)
        }),
            (e -= y))
        let t = e > v ? e - v : 0
        d = d.map((e) => (e <= 0 ? -m : e > t ? t + h : e))
    }
    if (n.centerInsufficientSlides) {
        let e = 0
        if (
            (p.forEach((t) => {
                e += t + (y || 0)
            }),
            (e -= y),
            e < v)
        ) {
            let t = (v - e) / 2
            ;(d.forEach((e, n) => {
                d[n] = e - t
            }),
                f.forEach((e, n) => {
                    f[n] = e + t
                }))
        }
    }
    if (
        (Object.assign(e, {
            slides: l,
            snapGrid: d,
            slidesGrid: f,
            slidesSizesGrid: p,
        }),
        n.centeredSlides && n.cssMode && !n.centeredSlidesBounds)
    ) {
        ;(kt(r, `--swiper-centered-offset-before`, `${-d[0]}px`),
            kt(
                r,
                `--swiper-centered-offset-after`,
                `${e.size / 2 - p[p.length - 1] / 2}px`,
            ))
        let t = -e.snapGrid[0],
            n = -e.slidesGrid[0]
        ;((e.snapGrid = e.snapGrid.map((e) => e + t)),
            (e.slidesGrid = e.slidesGrid.map((e) => e + n)))
    }
    if (
        (u !== c && e.emit(`slidesLengthChange`),
        d.length !== g &&
            (e.params.watchOverflow && e.checkOverflow(),
            e.emit(`snapGridLengthChange`)),
        f.length !== _ && e.emit(`slidesGridLengthChange`),
        n.watchSlidesProgress && e.updateSlidesOffset(),
        e.emit(`slidesUpdated`),
        !s && !n.cssMode && (n.effect === `slide` || n.effect === `fade`))
    ) {
        let t = `${n.containerModifierClass}backface-hidden`,
            r = e.el.classList.contains(t)
        u <= n.maxBackfaceHiddenSlides
            ? r || e.el.classList.add(t)
            : r && e.el.classList.remove(t)
    }
}
function on(e) {
    let t = this,
        n = [],
        r = t.virtual && t.params.virtual.enabled,
        i = 0,
        a
    typeof e == `number`
        ? t.setTransition(e)
        : e === !0 && t.setTransition(t.params.speed)
    let o = (e) => (r ? t.slides[t.getSlideIndexByData(e)] : t.slides[e])
    if (t.params.slidesPerView !== `auto` && t.params.slidesPerView > 1)
        if (t.params.centeredSlides)
            (t.visibleSlides || []).forEach((e) => {
                n.push(e)
            })
        else
            for (a = 0; a < Math.ceil(t.params.slidesPerView); a += 1) {
                let e = t.activeIndex + a
                if (e > t.slides.length && !r) break
                n.push(o(e))
            }
    else n.push(o(t.activeIndex))
    for (a = 0; a < n.length; a += 1)
        if (n[a] !== void 0) {
            let e = n[a].offsetHeight
            i = e > i ? e : i
        }
    ;(i || i === 0) && (t.wrapperEl.style.height = `${i}px`)
}
function sn() {
    let e = this,
        t = e.slides,
        n = e.isElement
            ? e.isHorizontal()
                ? e.wrapperEl.offsetLeft
                : e.wrapperEl.offsetTop
            : 0
    for (let r = 0; r < t.length; r += 1)
        t[r].swiperSlideOffset =
            (e.isHorizontal() ? t[r].offsetLeft : t[r].offsetTop) -
            n -
            e.cssOverflowAdjustment()
}
var cn = (e, t, n) => {
    t && !e.classList.contains(n)
        ? e.classList.add(n)
        : !t && e.classList.contains(n) && e.classList.remove(n)
}
function ln(e = (this && this.translate) || 0) {
    let t = this,
        n = t.params,
        { slides: r, rtlTranslate: i, snapGrid: a } = t
    if (r.length === 0) return
    r[0].swiperSlideOffset === void 0 && t.updateSlidesOffset()
    let o = -e
    ;(i && (o = e), (t.visibleSlidesIndexes = []), (t.visibleSlides = []))
    let s = n.spaceBetween
    typeof s == `string` && s.indexOf(`%`) >= 0
        ? (s = (parseFloat(s.replace(`%`, ``)) / 100) * t.size)
        : typeof s == `string` && (s = parseFloat(s))
    for (let e = 0; e < r.length; e += 1) {
        let c = r[e],
            l = c.swiperSlideOffset
        n.cssMode && n.centeredSlides && (l -= r[0].swiperSlideOffset)
        let u =
                (o + (n.centeredSlides ? t.minTranslate() : 0) - l) /
                (c.swiperSlideSize + s),
            d =
                (o - a[0] + (n.centeredSlides ? t.minTranslate() : 0) - l) /
                (c.swiperSlideSize + s),
            f = -(o - l),
            p = f + t.slidesSizesGrid[e],
            m = f >= 0 && f <= t.size - t.slidesSizesGrid[e],
            h =
                (f >= 0 && f < t.size - 1) ||
                (p > 1 && p <= t.size) ||
                (f <= 0 && p >= t.size)
        ;(h && (t.visibleSlides.push(c), t.visibleSlidesIndexes.push(e)),
            cn(c, h, n.slideVisibleClass),
            cn(c, m, n.slideFullyVisibleClass),
            (c.progress = i ? -u : u),
            (c.originalProgress = i ? -d : d))
    }
}
function un(e) {
    let t = this
    if (e === void 0) {
        let n = t.rtlTranslate ? -1 : 1
        e = (t && t.translate && t.translate * n) || 0
    }
    let n = t.params,
        r = t.maxTranslate() - t.minTranslate(),
        { progress: i, isBeginning: a, isEnd: o, progressLoop: s } = t,
        c = a,
        l = o
    if (r === 0) ((i = 0), (a = !0), (o = !0))
    else {
        i = (e - t.minTranslate()) / r
        let n = Math.abs(e - t.minTranslate()) < 1,
            s = Math.abs(e - t.maxTranslate()) < 1
        ;((a = n || i <= 0), (o = s || i >= 1), n && (i = 0), s && (i = 1))
    }
    if (n.loop) {
        let n = t.getSlideIndexByData(0),
            r = t.getSlideIndexByData(t.slides.length - 1),
            i = t.slidesGrid[n],
            a = t.slidesGrid[r],
            o = t.slidesGrid[t.slidesGrid.length - 1],
            c = Math.abs(e)
        ;((s = c >= i ? (c - i) / o : (c + o - a) / o), s > 1 && --s)
    }
    ;(Object.assign(t, {
        progress: i,
        progressLoop: s,
        isBeginning: a,
        isEnd: o,
    }),
        (n.watchSlidesProgress || (n.centeredSlides && n.autoHeight)) &&
            t.updateSlidesProgress(e),
        a && !c && t.emit(`reachBeginning toEdge`),
        o && !l && t.emit(`reachEnd toEdge`),
        ((c && !a) || (l && !o)) && t.emit(`fromEdge`),
        t.emit(`progress`, i))
}
var dn = (e, t, n) => {
    t && !e.classList.contains(n)
        ? e.classList.add(n)
        : !t && e.classList.contains(n) && e.classList.remove(n)
}
function fn() {
    let e = this,
        { slides: t, params: n, slidesEl: r, activeIndex: i } = e,
        a = e.virtual && n.virtual.enabled,
        o = e.grid && n.grid && n.grid.rows > 1,
        s = (e) => Mt(r, `.${n.slideClass}${e}, swiper-slide${e}`)[0],
        c,
        l,
        u
    if (a)
        if (n.loop) {
            let t = i - e.virtual.slidesBefore
            ;(t < 0 && (t = e.virtual.slides.length + t),
                t >= e.virtual.slides.length && (t -= e.virtual.slides.length),
                (c = s(`[data-swiper-slide-index="${t}"]`)))
        } else c = s(`[data-swiper-slide-index="${i}"]`)
    else
        o
            ? ((c = t.find((e) => e.column === i)),
              (u = t.find((e) => e.column === i + 1)),
              (l = t.find((e) => e.column === i - 1)))
            : (c = t[i])
    ;(c &&
        (o ||
            ((u = Rt(c, `.${n.slideClass}, swiper-slide`)[0]),
            n.loop && !u && (u = t[0]),
            (l = Lt(c, `.${n.slideClass}, swiper-slide`)[0]),
            n.loop)),
        t.forEach((e) => {
            ;(dn(e, e === c, n.slideActiveClass),
                dn(e, e === u, n.slideNextClass),
                dn(e, e === l, n.slidePrevClass))
        }),
        e.emitSlidesClasses())
}
var pn = (e, t) => {
        if (!e || e.destroyed || !e.params) return
        let n = t.closest(
            e.isElement ? `swiper-slide` : `.${e.params.slideClass}`,
        )
        if (n) {
            let t = n.querySelector(`.${e.params.lazyPreloaderClass}`)
            ;(!t &&
                e.isElement &&
                (n.shadowRoot
                    ? (t = n.shadowRoot.querySelector(
                          `.${e.params.lazyPreloaderClass}`,
                      ))
                    : requestAnimationFrame(() => {
                          n.shadowRoot &&
                              ((t = n.shadowRoot.querySelector(
                                  `.${e.params.lazyPreloaderClass}`,
                              )),
                              t && !t.lazyPreloaderManaged && t.remove())
                      })),
                t && !t.lazyPreloaderManaged && t.remove())
        }
    },
    mn = (e, t) => {
        if (!e.slides[t]) return
        let n = e.slides[t].querySelector(`[loading="lazy"]`)
        n && n.removeAttribute(`loading`)
    },
    hn = (e) => {
        if (!e || e.destroyed || !e.params) return
        let t = e.params.lazyPreloadPrevNext,
            n = e.slides.length
        if (!n || !t || t < 0) return
        t = Math.min(t, n)
        let r =
                e.params.slidesPerView === `auto`
                    ? e.slidesPerViewDynamic()
                    : Math.ceil(e.params.slidesPerView),
            i = e.activeIndex
        if (e.params.grid && e.params.grid.rows > 1) {
            let n = i,
                a = [n - t]
            ;(a.push(...Array.from({ length: t }).map((e, t) => n + r + t)),
                e.slides.forEach((t, n) => {
                    a.includes(t.column) && mn(e, n)
                }))
            return
        }
        let a = i + r - 1
        if (e.params.rewind || e.params.loop)
            for (let r = i - t; r <= a + t; r += 1) {
                let t = ((r % n) + n) % n
                ;(t < i || t > a) && mn(e, t)
            }
        else
            for (
                let r = Math.max(i - t, 0);
                r <= Math.min(a + t, n - 1);
                r += 1
            )
                r !== i && (r > a || r < i) && mn(e, r)
    }
function gn(e) {
    let { slidesGrid: t, params: n } = e,
        r = e.rtlTranslate ? e.translate : -e.translate,
        i
    for (let e = 0; e < t.length; e += 1)
        t[e + 1] === void 0
            ? r >= t[e] && (i = e)
            : r >= t[e] && r < t[e + 1] - (t[e + 1] - t[e]) / 2
              ? (i = e)
              : r >= t[e] && r < t[e + 1] && (i = e + 1)
    return (n.normalizeSlideIndex && (i < 0 || i === void 0) && (i = 0), i)
}
function _n(e) {
    let t = this,
        n = t.rtlTranslate ? t.translate : -t.translate,
        {
            snapGrid: r,
            params: i,
            activeIndex: a,
            realIndex: o,
            snapIndex: s,
        } = t,
        c = e,
        l,
        u = (e) => {
            let n = e - t.virtual.slidesBefore
            return (
                n < 0 && (n = t.virtual.slides.length + n),
                n >= t.virtual.slides.length && (n -= t.virtual.slides.length),
                n
            )
        }
    if ((c === void 0 && (c = gn(t)), r.indexOf(n) >= 0)) l = r.indexOf(n)
    else {
        let e = Math.min(i.slidesPerGroupSkip, c)
        l = e + Math.floor((c - e) / i.slidesPerGroup)
    }
    if ((l >= r.length && (l = r.length - 1), c === a && !t.params.loop)) {
        l !== s && ((t.snapIndex = l), t.emit(`snapIndexChange`))
        return
    }
    if (c === a && t.params.loop && t.virtual && t.params.virtual.enabled) {
        t.realIndex = u(c)
        return
    }
    let d = t.grid && i.grid && i.grid.rows > 1,
        f
    if (t.virtual && i.virtual.enabled) f = i.loop ? u(c) : c
    else if (d) {
        let e = t.slides.find((e) => e.column === c),
            n = parseInt(e.getAttribute(`data-swiper-slide-index`), 10)
        ;(Number.isNaN(n) && (n = Math.max(t.slides.indexOf(e), 0)),
            (f = Math.floor(n / i.grid.rows)))
    } else if (t.slides[c]) {
        let e = t.slides[c].getAttribute(`data-swiper-slide-index`)
        f = e ? parseInt(e, 10) : c
    } else f = c
    ;(Object.assign(t, {
        previousSnapIndex: s,
        snapIndex: l,
        previousRealIndex: o,
        realIndex: f,
        previousIndex: a,
        activeIndex: c,
    }),
        t.initialized && hn(t),
        t.emit(`activeIndexChange`),
        t.emit(`snapIndexChange`),
        (t.initialized || t.params.runCallbacksOnInit) &&
            (o !== f && t.emit(`realIndexChange`), t.emit(`slideChange`)))
}
function vn(e, t) {
    let n = this,
        r = n.params,
        i = e.closest(`.${r.slideClass}, swiper-slide`)
    !i &&
        n.isElement &&
        t &&
        t.length > 1 &&
        t.includes(e) &&
        [...t.slice(t.indexOf(e) + 1, t.length)].forEach((e) => {
            !i &&
                e.matches &&
                e.matches(`.${r.slideClass}, swiper-slide`) &&
                (i = e)
        })
    let a = !1,
        o
    if (i) {
        for (let e = 0; e < n.slides.length; e += 1)
            if (n.slides[e] === i) {
                ;((a = !0), (o = e))
                break
            }
    }
    if (i && a)
        ((n.clickedSlide = i),
            n.virtual && n.params.virtual.enabled
                ? (n.clickedIndex = parseInt(
                      i.getAttribute(`data-swiper-slide-index`),
                      10,
                  ))
                : (n.clickedIndex = o))
    else {
        ;((n.clickedSlide = void 0), (n.clickedIndex = void 0))
        return
    }
    r.slideToClickedSlide &&
        n.clickedIndex !== void 0 &&
        n.clickedIndex !== n.activeIndex &&
        n.slideToClickedSlide()
}
var yn = {
    updateSize: rn,
    updateSlides: an,
    updateAutoHeight: on,
    updateSlidesOffset: sn,
    updateSlidesProgress: ln,
    updateProgress: un,
    updateSlidesClasses: fn,
    updateActiveIndex: _n,
    updateClickedSlide: vn,
}
function bn(e = this.isHorizontal() ? `x` : `y`) {
    let t = this,
        { params: n, rtlTranslate: r, translate: i, wrapperEl: a } = t
    if (n.virtualTranslate) return r ? -i : i
    if (n.cssMode) return i
    let o = Tt(a, e)
    return ((o += t.cssOverflowAdjustment()), r && (o = -o), o || 0)
}
function xn(e, t) {
    let n = this,
        { rtlTranslate: r, params: i, wrapperEl: a, progress: o } = n,
        s = 0,
        c = 0
    ;(n.isHorizontal() ? (s = r ? -e : e) : (c = e),
        i.roundLengths && ((s = Math.floor(s)), (c = Math.floor(c))),
        (n.previousTranslate = n.translate),
        (n.translate = n.isHorizontal() ? s : c),
        i.cssMode
            ? (a[n.isHorizontal() ? `scrollLeft` : `scrollTop`] =
                  n.isHorizontal() ? -s : -c)
            : i.virtualTranslate ||
              (n.isHorizontal()
                  ? (s -= n.cssOverflowAdjustment())
                  : (c -= n.cssOverflowAdjustment()),
              (a.style.transform = `translate3d(${s}px, ${c}px, 0px)`)))
    let l,
        u = n.maxTranslate() - n.minTranslate()
    ;((l = u === 0 ? 0 : (e - n.minTranslate()) / u),
        l !== o && n.updateProgress(e),
        n.emit(`setTranslate`, n.translate, t))
}
function Sn() {
    return -this.snapGrid[0]
}
function Cn() {
    return -this.snapGrid[this.snapGrid.length - 1]
}
function wn(e = 0, t = this.params.speed, n = !0, r = !0, i) {
    let a = this,
        { params: o, wrapperEl: s } = a
    if (a.animating && o.preventInteractionOnTransition) return !1
    let c = a.minTranslate(),
        l = a.maxTranslate(),
        u
    if (
        ((u = r && e > c ? c : r && e < l ? l : e),
        a.updateProgress(u),
        o.cssMode)
    ) {
        let e = a.isHorizontal()
        if (t === 0) s[e ? `scrollLeft` : `scrollTop`] = -u
        else {
            if (!a.support.smoothScroll)
                return (
                    At({
                        swiper: a,
                        targetPosition: -u,
                        side: e ? `left` : `top`,
                    }),
                    !0
                )
            s.scrollTo({ [e ? `left` : `top`]: -u, behavior: `smooth` })
        }
        return !0
    }
    return (
        t === 0
            ? (a.setTransition(0),
              a.setTranslate(u),
              n &&
                  (a.emit(`beforeTransitionStart`, t, i),
                  a.emit(`transitionEnd`)))
            : (a.setTransition(t),
              a.setTranslate(u),
              n &&
                  (a.emit(`beforeTransitionStart`, t, i),
                  a.emit(`transitionStart`)),
              a.animating ||
                  ((a.animating = !0),
                  (a.onTranslateToWrapperTransitionEnd ||= function (e) {
                      !a ||
                          a.destroyed ||
                          (e.target === this &&
                              (a.wrapperEl.removeEventListener(
                                  `transitionend`,
                                  a.onTranslateToWrapperTransitionEnd,
                              ),
                              (a.onTranslateToWrapperTransitionEnd = null),
                              delete a.onTranslateToWrapperTransitionEnd,
                              (a.animating = !1),
                              n && a.emit(`transitionEnd`)))
                  }),
                  a.wrapperEl.addEventListener(
                      `transitionend`,
                      a.onTranslateToWrapperTransitionEnd,
                  ))),
        !0
    )
}
var Tn = {
    getTranslate: bn,
    setTranslate: xn,
    minTranslate: Sn,
    maxTranslate: Cn,
    translateTo: wn,
}
function En(e, t) {
    let n = this
    ;(n.params.cssMode ||
        ((n.wrapperEl.style.transitionDuration = `${e}ms`),
        (n.wrapperEl.style.transitionDelay = e === 0 ? `0ms` : ``)),
        n.emit(`setTransition`, e, t))
}
function Dn({ swiper: e, runCallbacks: t, direction: n, step: r }) {
    let { activeIndex: i, previousIndex: a } = e,
        o = n
    ;((o ||= i > a ? `next` : i < a ? `prev` : `reset`),
        e.emit(`transition${r}`),
        t && o === `reset`
            ? e.emit(`slideResetTransition${r}`)
            : t &&
              i !== a &&
              (e.emit(`slideChangeTransition${r}`),
              o === `next`
                  ? e.emit(`slideNextTransition${r}`)
                  : e.emit(`slidePrevTransition${r}`)))
}
function On(e = !0, t) {
    let n = this,
        { params: r } = n
    r.cssMode ||
        (r.autoHeight && n.updateAutoHeight(),
        Dn({ swiper: n, runCallbacks: e, direction: t, step: `Start` }))
}
function kn(e = !0, t) {
    let n = this,
        { params: r } = n
    ;((n.animating = !1),
        !r.cssMode &&
            (n.setTransition(0),
            Dn({ swiper: n, runCallbacks: e, direction: t, step: `End` })))
}
var An = { setTransition: En, transitionStart: On, transitionEnd: kn }
function jn(e = 0, t, n = !0, r, i) {
    typeof e == `string` && (e = parseInt(e, 10))
    let a = this,
        o = e
    o < 0 && (o = 0)
    let {
        params: s,
        snapGrid: c,
        slidesGrid: l,
        previousIndex: u,
        activeIndex: d,
        rtlTranslate: f,
        wrapperEl: p,
        enabled: m,
    } = a
    if (
        (!m && !r && !i) ||
        a.destroyed ||
        (a.animating && s.preventInteractionOnTransition)
    )
        return !1
    t === void 0 && (t = a.params.speed)
    let h = Math.min(a.params.slidesPerGroupSkip, o),
        g = h + Math.floor((o - h) / a.params.slidesPerGroup)
    g >= c.length && (g = c.length - 1)
    let _ = -c[g]
    if (s.normalizeSlideIndex)
        for (let e = 0; e < l.length; e += 1) {
            let t = -Math.floor(_ * 100),
                n = Math.floor(l[e] * 100),
                r = Math.floor(l[e + 1] * 100)
            l[e + 1] === void 0
                ? t >= n && (o = e)
                : t >= n && t < r - (r - n) / 2
                  ? (o = e)
                  : t >= n && t < r && (o = e + 1)
        }
    if (
        a.initialized &&
        o !== d &&
        ((!a.allowSlideNext &&
            (f
                ? _ > a.translate && _ > a.minTranslate()
                : _ < a.translate && _ < a.minTranslate())) ||
            (!a.allowSlidePrev &&
                _ > a.translate &&
                _ > a.maxTranslate() &&
                (d || 0) !== o))
    )
        return !1
    ;(o !== (u || 0) && n && a.emit(`beforeSlideChangeStart`),
        a.updateProgress(_))
    let v
    v = o > d ? `next` : o < d ? `prev` : `reset`
    let y = a.virtual && a.params.virtual.enabled
    if (!(y && i) && ((f && -_ === a.translate) || (!f && _ === a.translate)))
        return (
            a.updateActiveIndex(o),
            s.autoHeight && a.updateAutoHeight(),
            a.updateSlidesClasses(),
            s.effect !== `slide` && a.setTranslate(_),
            v !== `reset` && (a.transitionStart(n, v), a.transitionEnd(n, v)),
            !1
        )
    if (s.cssMode) {
        let e = a.isHorizontal(),
            n = f ? _ : -_
        if (t === 0)
            (y &&
                ((a.wrapperEl.style.scrollSnapType = `none`),
                (a._immediateVirtual = !0)),
                y && !a._cssModeVirtualInitialSet && a.params.initialSlide > 0
                    ? ((a._cssModeVirtualInitialSet = !0),
                      requestAnimationFrame(() => {
                          p[e ? `scrollLeft` : `scrollTop`] = n
                      }))
                    : (p[e ? `scrollLeft` : `scrollTop`] = n),
                y &&
                    requestAnimationFrame(() => {
                        ;((a.wrapperEl.style.scrollSnapType = ``),
                            (a._immediateVirtual = !1))
                    }))
        else {
            if (!a.support.smoothScroll)
                return (
                    At({
                        swiper: a,
                        targetPosition: n,
                        side: e ? `left` : `top`,
                    }),
                    !0
                )
            p.scrollTo({ [e ? `left` : `top`]: n, behavior: `smooth` })
        }
        return !0
    }
    let b = $t().isSafari
    return (
        y && !i && b && a.isElement && a.virtual.update(!1, !1, o),
        a.setTransition(t),
        a.setTranslate(_),
        a.updateActiveIndex(o),
        a.updateSlidesClasses(),
        a.emit(`beforeTransitionStart`, t, r),
        a.transitionStart(n, v),
        t === 0
            ? a.transitionEnd(n, v)
            : a.animating ||
              ((a.animating = !0),
              (a.onSlideToWrapperTransitionEnd ||= function (e) {
                  !a ||
                      a.destroyed ||
                      (e.target === this &&
                          (a.wrapperEl.removeEventListener(
                              `transitionend`,
                              a.onSlideToWrapperTransitionEnd,
                          ),
                          (a.onSlideToWrapperTransitionEnd = null),
                          delete a.onSlideToWrapperTransitionEnd,
                          a.transitionEnd(n, v)))
              }),
              a.wrapperEl.addEventListener(
                  `transitionend`,
                  a.onSlideToWrapperTransitionEnd,
              )),
        !0
    )
}
function Mn(e = 0, t, n = !0, r) {
    typeof e == `string` && (e = parseInt(e, 10))
    let i = this
    if (i.destroyed) return
    t === void 0 && (t = i.params.speed)
    let a = i.grid && i.params.grid && i.params.grid.rows > 1,
        o = e
    if (i.params.loop)
        if (i.virtual && i.params.virtual.enabled) o += i.virtual.slidesBefore
        else {
            let e
            if (a) {
                let t = o * i.params.grid.rows
                e = i.slides.find(
                    (e) => e.getAttribute(`data-swiper-slide-index`) * 1 === t,
                ).column
            } else e = i.getSlideIndexByData(o)
            let t = a
                    ? Math.ceil(i.slides.length / i.params.grid.rows)
                    : i.slides.length,
                {
                    centeredSlides: n,
                    slidesOffsetBefore: s,
                    slidesOffsetAfter: c,
                } = i.params,
                l = n || !!s || !!c,
                u = i.params.slidesPerView
            u === `auto`
                ? (u = i.slidesPerViewDynamic())
                : ((u = Math.ceil(parseFloat(i.params.slidesPerView, 10))),
                  l && u % 2 == 0 && (u += 1))
            let d = t - e < u
            if (
                (l && (d ||= e < Math.ceil(u / 2)),
                r && l && i.params.slidesPerView !== `auto` && !a && (d = !1),
                d)
            ) {
                let n = l
                    ? e < i.activeIndex
                        ? `prev`
                        : `next`
                    : e - i.activeIndex - 1 < i.params.slidesPerView
                      ? `next`
                      : `prev`
                i.loopFix({
                    direction: n,
                    slideTo: !0,
                    activeSlideIndex: n === `next` ? e + 1 : e - t + 1,
                    slideRealIndex: n === `next` ? i.realIndex : void 0,
                })
            }
            if (a) {
                let e = o * i.params.grid.rows
                o = i.slides.find(
                    (t) => t.getAttribute(`data-swiper-slide-index`) * 1 === e,
                ).column
            } else o = i.getSlideIndexByData(o)
        }
    return (
        requestAnimationFrame(() => {
            i.slideTo(o, t, n, r)
        }),
        i
    )
}
function Nn(e, t = !0, n) {
    let r = this,
        { enabled: i, params: a, animating: o } = r
    if (!i || r.destroyed) return r
    e === void 0 && (e = r.params.speed)
    let s = a.slidesPerGroup
    a.slidesPerView === `auto` &&
        a.slidesPerGroup === 1 &&
        a.slidesPerGroupAuto &&
        (s = Math.max(r.slidesPerViewDynamic(`current`, !0), 1))
    let c = r.activeIndex < a.slidesPerGroupSkip ? 1 : s,
        l = r.virtual && a.virtual.enabled
    if (a.loop) {
        if (o && !l && a.loopPreventsSliding) return !1
        if (
            (r.loopFix({ direction: `next` }),
            (r._clientLeft = r.wrapperEl.clientLeft),
            r.activeIndex === r.slides.length - 1 && a.cssMode)
        )
            return (
                requestAnimationFrame(() => {
                    r.slideTo(r.activeIndex + c, e, t, n)
                }),
                !0
            )
    }
    return a.rewind && r.isEnd
        ? r.slideTo(0, e, t, n)
        : r.slideTo(r.activeIndex + c, e, t, n)
}
function Pn(e, t = !0, n) {
    let r = this,
        {
            params: i,
            snapGrid: a,
            slidesGrid: o,
            rtlTranslate: s,
            enabled: c,
            animating: l,
        } = r
    if (!c || r.destroyed) return r
    e === void 0 && (e = r.params.speed)
    let u = r.virtual && i.virtual.enabled
    if (i.loop) {
        if (l && !u && i.loopPreventsSliding) return !1
        ;(r.loopFix({ direction: `prev` }),
            (r._clientLeft = r.wrapperEl.clientLeft))
    }
    let d = s ? r.translate : -r.translate
    function f(e) {
        return e < 0 ? -Math.floor(Math.abs(e)) : Math.floor(e)
    }
    let p = f(d),
        m = a.map((e) => f(e)),
        h = i.freeMode && i.freeMode.enabled,
        g = a[m.indexOf(p) - 1]
    if (g === void 0 && (i.cssMode || h)) {
        let e
        ;(a.forEach((t, n) => {
            p >= t && (e = n)
        }),
            e !== void 0 && (g = h ? a[e] : a[e > 0 ? e - 1 : e]))
    }
    let _ = 0
    if (
        (g !== void 0 &&
            ((_ = o.indexOf(g)),
            _ < 0 && (_ = r.activeIndex - 1),
            i.slidesPerView === `auto` &&
                i.slidesPerGroup === 1 &&
                i.slidesPerGroupAuto &&
                ((_ = _ - r.slidesPerViewDynamic(`previous`, !0) + 1),
                (_ = Math.max(_, 0)))),
        i.rewind && r.isBeginning)
    ) {
        let i =
            r.params.virtual && r.params.virtual.enabled && r.virtual
                ? r.virtual.slides.length - 1
                : r.slides.length - 1
        return r.slideTo(i, e, t, n)
    } else if (i.loop && r.activeIndex === 0 && i.cssMode)
        return (
            requestAnimationFrame(() => {
                r.slideTo(_, e, t, n)
            }),
            !0
        )
    return r.slideTo(_, e, t, n)
}
function Fn(e, t = !0, n) {
    let r = this
    if (!r.destroyed)
        return (
            e === void 0 && (e = r.params.speed),
            r.slideTo(r.activeIndex, e, t, n)
        )
}
function In(e, t = !0, n, r = 0.5) {
    let i = this
    if (i.destroyed) return
    e === void 0 && (e = i.params.speed)
    let a = i.activeIndex,
        o = Math.min(i.params.slidesPerGroupSkip, a),
        s = o + Math.floor((a - o) / i.params.slidesPerGroup),
        c = i.rtlTranslate ? i.translate : -i.translate
    if (c >= i.snapGrid[s]) {
        let e = i.snapGrid[s],
            t = i.snapGrid[s + 1]
        c - e > (t - e) * r && (a += i.params.slidesPerGroup)
    } else {
        let e = i.snapGrid[s - 1],
            t = i.snapGrid[s]
        c - e <= (t - e) * r && (a -= i.params.slidesPerGroup)
    }
    return (
        (a = Math.max(a, 0)),
        (a = Math.min(a, i.slidesGrid.length - 1)),
        i.slideTo(a, e, t, n)
    )
}
function Ln() {
    let e = this
    if (e.destroyed) return
    let { params: t, slidesEl: n } = e,
        r =
            t.slidesPerView === `auto`
                ? e.slidesPerViewDynamic()
                : t.slidesPerView,
        i = e.getSlideIndexWhenGrid(e.clickedIndex),
        a,
        o = e.isElement ? `swiper-slide` : `.${t.slideClass}`,
        s = e.grid && e.params.grid && e.params.grid.rows > 1
    if (t.loop) {
        if (e.animating) return
        ;((a = parseInt(
            e.clickedSlide.getAttribute(`data-swiper-slide-index`),
            10,
        )),
            t.centeredSlides
                ? e.slideToLoop(a)
                : i >
                    (s
                        ? (e.slides.length - r) / 2 - (e.params.grid.rows - 1)
                        : e.slides.length - r)
                  ? (e.loopFix(),
                    (i = e.getSlideIndex(
                        Mt(n, `${o}[data-swiper-slide-index="${a}"]`)[0],
                    )),
                    St(() => {
                        e.slideTo(i)
                    }))
                  : e.slideTo(i))
    } else e.slideTo(i)
}
var Rn = {
    slideTo: jn,
    slideToLoop: Mn,
    slideNext: Nn,
    slidePrev: Pn,
    slideReset: Fn,
    slideToClosest: In,
    slideToClickedSlide: Ln,
}
function zn(e, t) {
    let n = this,
        { params: r, slidesEl: i } = n
    if (!r.loop || (n.virtual && n.params.virtual.enabled)) return
    let a = () => {
            Mt(i, `.${r.slideClass}, swiper-slide`).forEach((e, t) => {
                e.setAttribute(`data-swiper-slide-index`, t)
            })
        },
        o = () => {
            let e = Mt(i, `.${r.slideBlankClass}`)
            ;(e.forEach((e) => {
                e.remove()
            }),
                e.length > 0 && (n.recalcSlides(), n.updateSlides()))
        },
        s = n.grid && r.grid && r.grid.rows > 1
    r.loopAddBlankSlides && (r.slidesPerGroup > 1 || s) && o()
    let c = r.slidesPerGroup * (s ? r.grid.rows : 1),
        l = n.slides.length % c !== 0,
        u = s && n.slides.length % r.grid.rows !== 0,
        d = (e) => {
            for (let t = 0; t < e; t += 1) {
                let e = n.isElement
                    ? It(`swiper-slide`, [r.slideBlankClass])
                    : It(`div`, [r.slideClass, r.slideBlankClass])
                n.slidesEl.append(e)
            }
        }
    l
        ? (r.loopAddBlankSlides
              ? (d(c - (n.slides.length % c)),
                n.recalcSlides(),
                n.updateSlides())
              : Ft(
                    `Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)`,
                ),
          a())
        : (u &&
              (r.loopAddBlankSlides
                  ? (d(r.grid.rows - (n.slides.length % r.grid.rows)),
                    n.recalcSlides(),
                    n.updateSlides())
                  : Ft(
                        `Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)`,
                    )),
          a())
    let f = r.centeredSlides || !!r.slidesOffsetBefore || !!r.slidesOffsetAfter
    n.loopFix({ slideRealIndex: e, direction: f ? void 0 : `next`, initial: t })
}
function Bn({
    slideRealIndex: e,
    slideTo: t = !0,
    direction: n,
    setTranslate: r,
    activeSlideIndex: i,
    initial: a,
    byController: o,
    byMousewheel: s,
} = {}) {
    let c = this
    if (!c.params.loop) return
    c.emit(`beforeLoopFix`)
    let {
            slides: l,
            allowSlidePrev: u,
            allowSlideNext: d,
            slidesEl: f,
            params: p,
        } = c,
        {
            centeredSlides: m,
            slidesOffsetBefore: h,
            slidesOffsetAfter: g,
            initialSlide: _,
        } = p,
        v = m || !!h || !!g
    if (
        ((c.allowSlidePrev = !0),
        (c.allowSlideNext = !0),
        c.virtual && p.virtual.enabled)
    ) {
        ;(t &&
            (!v && c.snapIndex === 0
                ? c.slideTo(c.virtual.slides.length, 0, !1, !0)
                : v && c.snapIndex < p.slidesPerView
                  ? c.slideTo(c.virtual.slides.length + c.snapIndex, 0, !1, !0)
                  : c.snapIndex === c.snapGrid.length - 1 &&
                    c.slideTo(c.virtual.slidesBefore, 0, !1, !0)),
            (c.allowSlidePrev = u),
            (c.allowSlideNext = d),
            c.emit(`loopFix`))
        return
    }
    let y = p.slidesPerView
    y === `auto`
        ? (y = c.slidesPerViewDynamic())
        : ((y = Math.ceil(parseFloat(p.slidesPerView, 10))),
          v && y % 2 == 0 && (y += 1))
    let b = p.slidesPerGroupAuto ? y : p.slidesPerGroup,
        x = v ? Math.max(b, Math.ceil(y / 2)) : b
    ;(x % b !== 0 && (x += b - (x % b)),
        (x += p.loopAdditionalSlides),
        (c.loopedSlides = x))
    let S = c.grid && p.grid && p.grid.rows > 1
    l.length < y + x || (c.params.effect === `cards` && l.length < y + x * 2)
        ? Ft(
              `Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled or not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters`,
          )
        : S &&
          p.grid.fill === `row` &&
          Ft(
              'Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`',
          )
    let C = [],
        w = [],
        T = S ? Math.ceil(l.length / p.grid.rows) : l.length,
        E = a && T - _ < y && !v,
        D = E ? _ : c.activeIndex
    i === void 0
        ? (i = c.getSlideIndex(
              l.find((e) => e.classList.contains(p.slideActiveClass)),
          ))
        : (D = i)
    let O = n === `next` || !n,
        k = n === `prev` || !n,
        A = 0,
        j = 0,
        M = (S ? l[i].column : i) + (v && r === void 0 ? -y / 2 + 0.5 : 0)
    if (M < x) {
        A = Math.max(x - M, b)
        for (let e = 0; e < x - M; e += 1) {
            let t = e - Math.floor(e / T) * T
            if (S) {
                let e = T - t - 1
                for (let t = l.length - 1; t >= 0; --t)
                    l[t].column === e && C.push(t)
            } else C.push(T - t - 1)
        }
    } else if (M + y > T - x) {
        ;((j = Math.max(M - (T - x * 2), b)),
            E && (j = Math.max(j, y - T + _ + 1)))
        for (let e = 0; e < j; e += 1) {
            let t = e - Math.floor(e / T) * T
            S
                ? l.forEach((e, n) => {
                      e.column === t && w.push(n)
                  })
                : w.push(t)
        }
    }
    if (
        ((c.__preventObserver__ = !0),
        requestAnimationFrame(() => {
            c.__preventObserver__ = !1
        }),
        c.params.effect === `cards` &&
            l.length < y + x * 2 &&
            (w.includes(i) && w.splice(w.indexOf(i), 1),
            C.includes(i) && C.splice(C.indexOf(i), 1)),
        k &&
            C.forEach((e) => {
                ;((l[e].swiperLoopMoveDOM = !0),
                    f.prepend(l[e]),
                    (l[e].swiperLoopMoveDOM = !1))
            }),
        O &&
            w.forEach((e) => {
                ;((l[e].swiperLoopMoveDOM = !0),
                    f.append(l[e]),
                    (l[e].swiperLoopMoveDOM = !1))
            }),
        c.recalcSlides(),
        p.slidesPerView === `auto`
            ? c.updateSlides()
            : S &&
              ((C.length > 0 && k) || (w.length > 0 && O)) &&
              c.slides.forEach((e, t) => {
                  c.grid.updateSlide(t, e, c.slides)
              }),
        p.watchSlidesProgress && c.updateSlidesOffset(),
        t)
    ) {
        if (C.length > 0 && k) {
            if (e === void 0) {
                let e = c.slidesGrid[D],
                    t = c.slidesGrid[D + A] - e
                s
                    ? c.setTranslate(c.translate - t)
                    : (c.slideTo(D + Math.ceil(A), 0, !1, !0),
                      r &&
                          ((c.touchEventsData.startTranslate =
                              c.touchEventsData.startTranslate - t),
                          (c.touchEventsData.currentTranslate =
                              c.touchEventsData.currentTranslate - t)))
            } else if (r) {
                let e = S ? C.length / p.grid.rows : C.length
                ;(c.slideTo(c.activeIndex + e, 0, !1, !0),
                    (c.touchEventsData.currentTranslate = c.translate))
            }
        } else if (w.length > 0 && O)
            if (e === void 0) {
                let e = c.slidesGrid[D],
                    t = c.slidesGrid[D - j] - e
                s
                    ? c.setTranslate(c.translate - t)
                    : (c.slideTo(D - j, 0, !1, !0),
                      r &&
                          ((c.touchEventsData.startTranslate =
                              c.touchEventsData.startTranslate - t),
                          (c.touchEventsData.currentTranslate =
                              c.touchEventsData.currentTranslate - t)))
            } else {
                let e = S ? w.length / p.grid.rows : w.length
                c.slideTo(c.activeIndex - e, 0, !1, !0)
            }
    }
    if (
        ((c.allowSlidePrev = u),
        (c.allowSlideNext = d),
        c.controller && c.controller.control && !o)
    ) {
        let a = {
            slideRealIndex: e,
            direction: n,
            setTranslate: r,
            activeSlideIndex: i,
            byController: !0,
        }
        Array.isArray(c.controller.control)
            ? c.controller.control.forEach((e) => {
                  !e.destroyed &&
                      e.params.loop &&
                      e.loopFix({
                          ...a,
                          slideTo:
                              e.params.slidesPerView === p.slidesPerView
                                  ? t
                                  : !1,
                      })
              })
            : c.controller.control instanceof c.constructor &&
              c.controller.control.params.loop &&
              c.controller.control.loopFix({
                  ...a,
                  slideTo:
                      c.controller.control.params.slidesPerView ===
                      p.slidesPerView
                          ? t
                          : !1,
              })
    }
    c.emit(`loopFix`)
}
function Vn() {
    let e = this,
        { params: t, slidesEl: n } = e
    if (!t.loop || !n || (e.virtual && e.params.virtual.enabled)) return
    e.recalcSlides()
    let r = []
    ;(e.slides.forEach((e) => {
        let t =
            e.swiperSlideIndex === void 0
                ? e.getAttribute(`data-swiper-slide-index`) * 1
                : e.swiperSlideIndex
        r[t] = e
    }),
        e.slides.forEach((e) => {
            e.removeAttribute(`data-swiper-slide-index`)
        }),
        r.forEach((e) => {
            n.append(e)
        }),
        e.recalcSlides(),
        e.slideTo(e.realIndex, 0))
}
var Hn = { loopCreate: zn, loopFix: Bn, loopDestroy: Vn }
function Un(e) {
    let t = this
    if (
        !t.params.simulateTouch ||
        (t.params.watchOverflow && t.isLocked) ||
        t.params.cssMode
    )
        return
    let n = t.params.touchEventsTarget === `container` ? t.el : t.wrapperEl
    ;(t.isElement && (t.__preventObserver__ = !0),
        (n.style.cursor = `move`),
        (n.style.cursor = e ? `grabbing` : `grab`),
        t.isElement &&
            requestAnimationFrame(() => {
                t.__preventObserver__ = !1
            }))
}
function Wn() {
    let e = this
    ;(e.params.watchOverflow && e.isLocked) ||
        e.params.cssMode ||
        (e.isElement && (e.__preventObserver__ = !0),
        (e[
            e.params.touchEventsTarget === `container` ? `el` : `wrapperEl`
        ].style.cursor = ``),
        e.isElement &&
            requestAnimationFrame(() => {
                e.__preventObserver__ = !1
            }))
}
var Gn = { setGrabCursor: Un, unsetGrabCursor: Wn }
function Kn(e, t = this) {
    function n(t) {
        if (!t || t === vt() || t === Q()) return null
        t.assignedSlot && (t = t.assignedSlot)
        let r = t.closest(e)
        return !r && !t.getRootNode ? null : r || n(t.getRootNode().host)
    }
    return n(t)
}
function qn(e, t, n) {
    let r = Q(),
        { params: i } = e,
        a = i.edgeSwipeDetection,
        o = i.edgeSwipeThreshold
    return a && (n <= o || n >= r.innerWidth - o)
        ? a === `prevent`
            ? (t.preventDefault(), !0)
            : !1
        : !0
}
function Jn(e) {
    let t = this,
        n = vt(),
        r = e
    r.originalEvent && (r = r.originalEvent)
    let i = t.touchEventsData
    if (r.type === `pointerdown`) {
        if (i.pointerId !== null && i.pointerId !== r.pointerId) return
        i.pointerId = r.pointerId
    } else
        r.type === `touchstart` &&
            r.targetTouches.length === 1 &&
            (i.touchId = r.targetTouches[0].identifier)
    if (r.type === `touchstart`) {
        qn(t, r, r.targetTouches[0].pageX)
        return
    }
    let { params: a, touches: o, enabled: s } = t
    if (
        !s ||
        (!a.simulateTouch && r.pointerType === `mouse`) ||
        (t.animating && a.preventInteractionOnTransition)
    )
        return
    !t.animating && a.cssMode && a.loop && t.loopFix()
    let c = r.target
    if (
        (a.touchEventsTarget === `wrapper` && !Pt(c, t.wrapperEl)) ||
        (`which` in r && r.which === 3) ||
        (`button` in r && r.button > 0) ||
        (i.isTouched && i.isMoved)
    )
        return
    let l = !!a.noSwipingClass && a.noSwipingClass !== ``,
        u = r.composedPath ? r.composedPath() : r.path
    l && r.target && r.target.shadowRoot && u && (c = u[0])
    let d = a.noSwipingSelector ? a.noSwipingSelector : `.${a.noSwipingClass}`,
        f = !!(r.target && r.target.shadowRoot)
    if (a.noSwiping && (f ? Kn(d, c) : c.closest(d))) {
        t.allowClick = !0
        return
    }
    if (a.swipeHandler && !c.closest(a.swipeHandler)) return
    ;((o.currentX = r.pageX), (o.currentY = r.pageY))
    let p = o.currentX,
        m = o.currentY
    if (!qn(t, r, p)) return
    ;(Object.assign(i, {
        isTouched: !0,
        isMoved: !1,
        allowTouchCallbacks: !0,
        isScrolling: void 0,
        startMoving: void 0,
    }),
        (o.startX = p),
        (o.startY = m),
        (i.touchStartTime = Ct()),
        (t.allowClick = !0),
        t.updateSize(),
        (t.swipeDirection = void 0),
        a.threshold > 0 && (i.allowThresholdMove = !1))
    let h = !0
    ;(c.matches(i.focusableElements) &&
        ((h = !1), c.nodeName === `SELECT` && (i.isTouched = !1)),
        n.activeElement &&
            n.activeElement.matches(i.focusableElements) &&
            n.activeElement !== c &&
            (r.pointerType === `mouse` ||
                (r.pointerType !== `mouse` &&
                    !c.matches(i.focusableElements))) &&
            n.activeElement.blur())
    let g = h && t.allowTouchMove && a.touchStartPreventDefault
    ;((a.touchStartForcePreventDefault || g) &&
        !c.isContentEditable &&
        r.preventDefault(),
        a.freeMode &&
            a.freeMode.enabled &&
            t.freeMode &&
            t.animating &&
            !a.cssMode &&
            t.freeMode.onTouchStart(),
        t.emit(`touchStart`, r))
}
function Yn(e) {
    let t = vt(),
        n = this,
        r = n.touchEventsData,
        { params: i, touches: a, rtlTranslate: o, enabled: s } = n
    if (!s || (!i.simulateTouch && e.pointerType === `mouse`)) return
    let c = e
    if (
        (c.originalEvent && (c = c.originalEvent),
        c.type === `pointermove` &&
            (r.touchId !== null || c.pointerId !== r.pointerId))
    )
        return
    let l
    if (c.type === `touchmove`) {
        if (
            ((l = [...c.changedTouches].find(
                (e) => e.identifier === r.touchId,
            )),
            !l || l.identifier !== r.touchId)
        )
            return
    } else l = c
    if (!r.isTouched) {
        r.startMoving && r.isScrolling && n.emit(`touchMoveOpposite`, c)
        return
    }
    let u = l.pageX,
        d = l.pageY
    if (c.preventedByNestedSwiper) {
        ;((a.startX = u), (a.startY = d))
        return
    }
    if (!n.allowTouchMove) {
        ;(c.target.matches(r.focusableElements) || (n.allowClick = !1),
            r.isTouched &&
                (Object.assign(a, {
                    startX: u,
                    startY: d,
                    currentX: u,
                    currentY: d,
                }),
                (r.touchStartTime = Ct())))
        return
    }
    if (i.touchReleaseOnEdges && !i.loop) {
        if (n.isVertical()) {
            if (
                (d < a.startY && n.translate <= n.maxTranslate()) ||
                (d > a.startY && n.translate >= n.minTranslate())
            ) {
                ;((r.isTouched = !1), (r.isMoved = !1))
                return
            }
        } else if (
            o &&
            ((u > a.startX && -n.translate <= n.maxTranslate()) ||
                (u < a.startX && -n.translate >= n.minTranslate()))
        )
            return
        else if (
            !o &&
            ((u < a.startX && n.translate <= n.maxTranslate()) ||
                (u > a.startX && n.translate >= n.minTranslate()))
        )
            return
    }
    if (
        (t.activeElement &&
            t.activeElement.matches(r.focusableElements) &&
            t.activeElement !== c.target &&
            c.pointerType !== `mouse` &&
            t.activeElement.blur(),
        t.activeElement &&
            c.target === t.activeElement &&
            c.target.matches(r.focusableElements))
    ) {
        ;((r.isMoved = !0), (n.allowClick = !1))
        return
    }
    ;(r.allowTouchCallbacks && n.emit(`touchMove`, c),
        (a.previousX = a.currentX),
        (a.previousY = a.currentY),
        (a.currentX = u),
        (a.currentY = d))
    let f = a.currentX - a.startX,
        p = a.currentY - a.startY
    if (n.params.threshold && Math.sqrt(f ** 2 + p ** 2) < n.params.threshold)
        return
    if (r.isScrolling === void 0) {
        let e
        ;(n.isHorizontal() && a.currentY === a.startY) ||
        (n.isVertical() && a.currentX === a.startX)
            ? (r.isScrolling = !1)
            : f * f + p * p >= 25 &&
              ((e = (Math.atan2(Math.abs(p), Math.abs(f)) * 180) / Math.PI),
              (r.isScrolling = n.isHorizontal()
                  ? e > i.touchAngle
                  : 90 - e > i.touchAngle))
    }
    if (
        (r.isScrolling && n.emit(`touchMoveOpposite`, c),
        r.startMoving === void 0 &&
            (a.currentX !== a.startX || a.currentY !== a.startY) &&
            (r.startMoving = !0),
        r.isScrolling ||
            (c.type === `touchmove` && r.preventTouchMoveFromPointerMove))
    ) {
        r.isTouched = !1
        return
    }
    if (!r.startMoving) return
    ;((n.allowClick = !1),
        !i.cssMode && c.cancelable && c.preventDefault(),
        i.touchMoveStopPropagation && !i.nested && c.stopPropagation())
    let m = n.isHorizontal() ? f : p,
        h = n.isHorizontal()
            ? a.currentX - a.previousX
            : a.currentY - a.previousY
    ;(i.oneWayMovement &&
        ((m = Math.abs(m) * (o ? 1 : -1)), (h = Math.abs(h) * (o ? 1 : -1))),
        (a.diff = m),
        (m *= i.touchRatio),
        o && ((m = -m), (h = -h)))
    let g = n.touchesDirection
    ;((n.swipeDirection = m > 0 ? `prev` : `next`),
        (n.touchesDirection = h > 0 ? `prev` : `next`))
    let _ = n.params.loop && !i.cssMode,
        v =
            (n.touchesDirection === `next` && n.allowSlideNext) ||
            (n.touchesDirection === `prev` && n.allowSlidePrev)
    if (!r.isMoved) {
        if (
            (_ && v && n.loopFix({ direction: n.swipeDirection }),
            (r.startTranslate = n.getTranslate()),
            n.setTransition(0),
            n.animating)
        ) {
            let e = new window.CustomEvent(`transitionend`, {
                bubbles: !0,
                cancelable: !0,
                detail: { bySwiperTouchMove: !0 },
            })
            n.wrapperEl.dispatchEvent(e)
        }
        ;((r.allowMomentumBounce = !1),
            i.grabCursor &&
                (n.allowSlideNext === !0 || n.allowSlidePrev === !0) &&
                n.setGrabCursor(!0),
            n.emit(`sliderFirstMove`, c))
    }
    if (
        (new Date().getTime(),
        i._loopSwapReset !== !1 &&
            r.isMoved &&
            r.allowThresholdMove &&
            g !== n.touchesDirection &&
            _ &&
            v &&
            Math.abs(m) >= 1)
    ) {
        ;(Object.assign(a, {
            startX: u,
            startY: d,
            currentX: u,
            currentY: d,
            startTranslate: r.currentTranslate,
        }),
            (r.loopSwapReset = !0),
            (r.startTranslate = r.currentTranslate))
        return
    }
    ;(n.emit(`sliderMove`, c),
        (r.isMoved = !0),
        (r.currentTranslate = m + r.startTranslate))
    let y = !0,
        b = i.resistanceRatio
    if (
        (i.touchReleaseOnEdges && (b = 0),
        m > 0
            ? (_ &&
                  v &&
                  r.allowThresholdMove &&
                  r.currentTranslate >
                      (i.centeredSlides
                          ? n.minTranslate() -
                            n.slidesSizesGrid[n.activeIndex + 1] -
                            (i.slidesPerView !== `auto` &&
                            n.slides.length - i.slidesPerView >= 2
                                ? n.slidesSizesGrid[n.activeIndex + 1] +
                                  n.params.spaceBetween
                                : 0) -
                            n.params.spaceBetween
                          : n.minTranslate()) &&
                  n.loopFix({
                      direction: `prev`,
                      setTranslate: !0,
                      activeSlideIndex: 0,
                  }),
              r.currentTranslate > n.minTranslate() &&
                  ((y = !1),
                  i.resistance &&
                      (r.currentTranslate =
                          n.minTranslate() -
                          1 +
                          (-n.minTranslate() + r.startTranslate + m) ** b)))
            : m < 0 &&
              (_ &&
                  v &&
                  r.allowThresholdMove &&
                  r.currentTranslate <
                      (i.centeredSlides
                          ? n.maxTranslate() +
                            n.slidesSizesGrid[n.slidesSizesGrid.length - 1] +
                            n.params.spaceBetween +
                            (i.slidesPerView !== `auto` &&
                            n.slides.length - i.slidesPerView >= 2
                                ? n.slidesSizesGrid[
                                      n.slidesSizesGrid.length - 1
                                  ] + n.params.spaceBetween
                                : 0)
                          : n.maxTranslate()) &&
                  n.loopFix({
                      direction: `next`,
                      setTranslate: !0,
                      activeSlideIndex:
                          n.slides.length -
                          (i.slidesPerView === `auto`
                              ? n.slidesPerViewDynamic()
                              : Math.ceil(parseFloat(i.slidesPerView, 10))),
                  }),
              r.currentTranslate < n.maxTranslate() &&
                  ((y = !1),
                  i.resistance &&
                      (r.currentTranslate =
                          n.maxTranslate() +
                          1 -
                          (n.maxTranslate() - r.startTranslate - m) ** b))),
        y && (c.preventedByNestedSwiper = !0),
        !n.allowSlideNext &&
            n.swipeDirection === `next` &&
            r.currentTranslate < r.startTranslate &&
            (r.currentTranslate = r.startTranslate),
        !n.allowSlidePrev &&
            n.swipeDirection === `prev` &&
            r.currentTranslate > r.startTranslate &&
            (r.currentTranslate = r.startTranslate),
        !n.allowSlidePrev &&
            !n.allowSlideNext &&
            (r.currentTranslate = r.startTranslate),
        i.threshold > 0)
    )
        if (Math.abs(m) > i.threshold || r.allowThresholdMove) {
            if (!r.allowThresholdMove) {
                ;((r.allowThresholdMove = !0),
                    (a.startX = a.currentX),
                    (a.startY = a.currentY),
                    (r.currentTranslate = r.startTranslate),
                    (a.diff = n.isHorizontal()
                        ? a.currentX - a.startX
                        : a.currentY - a.startY))
                return
            }
        } else {
            r.currentTranslate = r.startTranslate
            return
        }
    !i.followFinger ||
        i.cssMode ||
        (((i.freeMode && i.freeMode.enabled && n.freeMode) ||
            i.watchSlidesProgress) &&
            (n.updateActiveIndex(), n.updateSlidesClasses()),
        i.freeMode &&
            i.freeMode.enabled &&
            n.freeMode &&
            n.freeMode.onTouchMove(),
        n.updateProgress(r.currentTranslate),
        n.setTranslate(r.currentTranslate))
}
function Xn(e) {
    let t = this,
        n = t.touchEventsData,
        r = e
    r.originalEvent && (r = r.originalEvent)
    let i
    if (!(r.type === `touchend` || r.type === `touchcancel`)) {
        if (n.touchId !== null || r.pointerId !== n.pointerId) return
        i = r
    } else if (
        ((i = [...r.changedTouches].find((e) => e.identifier === n.touchId)),
        !i || i.identifier !== n.touchId)
    )
        return
    if (
        [`pointercancel`, `pointerout`, `pointerleave`, `contextmenu`].includes(
            r.type,
        ) &&
        !(
            [`pointercancel`, `contextmenu`].includes(r.type) &&
            (t.browser.isSafari || t.browser.isWebView)
        )
    )
        return
    ;((n.pointerId = null), (n.touchId = null))
    let {
        params: a,
        touches: o,
        rtlTranslate: s,
        slidesGrid: c,
        enabled: l,
    } = t
    if (!l || (!a.simulateTouch && r.pointerType === `mouse`)) return
    if (
        (n.allowTouchCallbacks && t.emit(`touchEnd`, r),
        (n.allowTouchCallbacks = !1),
        !n.isTouched)
    ) {
        ;(n.isMoved && a.grabCursor && t.setGrabCursor(!1),
            (n.isMoved = !1),
            (n.startMoving = !1))
        return
    }
    a.grabCursor &&
        n.isMoved &&
        n.isTouched &&
        (t.allowSlideNext === !0 || t.allowSlidePrev === !0) &&
        t.setGrabCursor(!1)
    let u = Ct(),
        d = u - n.touchStartTime
    if (t.allowClick) {
        let e = r.path || (r.composedPath && r.composedPath())
        ;(t.updateClickedSlide((e && e[0]) || r.target, e),
            t.emit(`tap click`, r),
            d < 300 &&
                u - n.lastClickTime < 300 &&
                t.emit(`doubleTap doubleClick`, r))
    }
    if (
        ((n.lastClickTime = Ct()),
        St(() => {
            t.destroyed || (t.allowClick = !0)
        }),
        !n.isTouched ||
            !n.isMoved ||
            !t.swipeDirection ||
            (o.diff === 0 && !n.loopSwapReset) ||
            (n.currentTranslate === n.startTranslate && !n.loopSwapReset))
    ) {
        ;((n.isTouched = !1), (n.isMoved = !1), (n.startMoving = !1))
        return
    }
    ;((n.isTouched = !1), (n.isMoved = !1), (n.startMoving = !1))
    let f
    if (
        ((f = a.followFinger
            ? s
                ? t.translate
                : -t.translate
            : -n.currentTranslate),
        a.cssMode)
    )
        return
    if (a.freeMode && a.freeMode.enabled) {
        t.freeMode.onTouchEnd({ currentPos: f })
        return
    }
    let p = f >= -t.maxTranslate() && !t.params.loop,
        m = 0,
        h = t.slidesSizesGrid[0]
    for (
        let e = 0;
        e < c.length;
        e += e < a.slidesPerGroupSkip ? 1 : a.slidesPerGroup
    ) {
        let t = e < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup
        c[e + t] === void 0
            ? (p || f >= c[e]) &&
              ((m = e), (h = c[c.length - 1] - c[c.length - 2]))
            : (p || (f >= c[e] && f < c[e + t])) &&
              ((m = e), (h = c[e + t] - c[e]))
    }
    let g = null,
        _ = null
    a.rewind &&
        (t.isBeginning
            ? (_ =
                  a.virtual && a.virtual.enabled && t.virtual
                      ? t.virtual.slides.length - 1
                      : t.slides.length - 1)
            : t.isEnd && (g = 0))
    let v = (f - c[m]) / h,
        y = m < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup
    if (d > a.longSwipesMs) {
        if (!a.longSwipes) {
            t.slideTo(t.activeIndex)
            return
        }
        ;(t.swipeDirection === `next` &&
            (v >= a.longSwipesRatio
                ? t.slideTo(a.rewind && t.isEnd ? g : m + y)
                : t.slideTo(m)),
            t.swipeDirection === `prev` &&
                (v > 1 - a.longSwipesRatio
                    ? t.slideTo(m + y)
                    : _ !== null && v < 0 && Math.abs(v) > a.longSwipesRatio
                      ? t.slideTo(_)
                      : t.slideTo(m)))
    } else {
        if (!a.shortSwipes) {
            t.slideTo(t.activeIndex)
            return
        }
        t.navigation &&
        (r.target === t.navigation.nextEl || r.target === t.navigation.prevEl)
            ? r.target === t.navigation.nextEl
                ? t.slideTo(m + y)
                : t.slideTo(m)
            : (t.swipeDirection === `next` && t.slideTo(g === null ? m + y : g),
              t.swipeDirection === `prev` && t.slideTo(_ === null ? m : _))
    }
}
function Zn() {
    let e = this,
        { params: t, el: n } = e
    if (n && n.offsetWidth === 0) return
    t.breakpoints && e.setBreakpoint()
    let { allowSlideNext: r, allowSlidePrev: i, snapGrid: a } = e,
        o = e.virtual && e.params.virtual.enabled
    ;((e.allowSlideNext = !0),
        (e.allowSlidePrev = !0),
        e.updateSize(),
        e.updateSlides(),
        e.updateSlidesClasses())
    let s = o && t.loop
    if (
        (t.slidesPerView === `auto` || t.slidesPerView > 1) &&
        e.isEnd &&
        !e.isBeginning &&
        !e.params.centeredSlides &&
        !s
    ) {
        let t = o ? e.virtual.slides : e.slides
        e.slideTo(t.length - 1, 0, !1, !0)
    } else
        e.params.loop && !o
            ? e.slideToLoop(e.realIndex, 0, !1, !0)
            : e.slideTo(e.activeIndex, 0, !1, !0)
    ;(e.autoplay &&
        e.autoplay.running &&
        e.autoplay.paused &&
        (clearTimeout(e.autoplay.resizeTimeout),
        (e.autoplay.resizeTimeout = setTimeout(() => {
            e.autoplay &&
                e.autoplay.running &&
                e.autoplay.paused &&
                e.autoplay.resume()
        }, 500))),
        (e.allowSlidePrev = i),
        (e.allowSlideNext = r),
        e.params.watchOverflow && a !== e.snapGrid && e.checkOverflow())
}
function Qn(e) {
    let t = this
    t.enabled &&
        (t.allowClick ||
            (t.params.preventClicks && e.preventDefault(),
            t.params.preventClicksPropagation &&
                t.animating &&
                (e.stopPropagation(), e.stopImmediatePropagation())))
}
function $n() {
    let e = this,
        { wrapperEl: t, rtlTranslate: n, enabled: r } = e
    if (!r) return
    ;((e.previousTranslate = e.translate),
        e.isHorizontal()
            ? (e.translate = -t.scrollLeft)
            : (e.translate = -t.scrollTop),
        e.translate === 0 && (e.translate = 0),
        e.updateActiveIndex(),
        e.updateSlidesClasses())
    let i,
        a = e.maxTranslate() - e.minTranslate()
    ;((i = a === 0 ? 0 : (e.translate - e.minTranslate()) / a),
        i !== e.progress && e.updateProgress(n ? -e.translate : e.translate),
        e.emit(`setTranslate`, e.translate, !1))
}
function er(e) {
    let t = this
    ;(pn(t, e.target),
        !(
            t.params.cssMode ||
            (t.params.slidesPerView !== `auto` && !t.params.autoHeight)
        ) && t.update())
}
function tr() {
    let e = this
    e.documentTouchHandlerProceeded ||
        ((e.documentTouchHandlerProceeded = !0),
        e.params.touchReleaseOnEdges && (e.el.style.touchAction = `auto`))
}
var nr = (e, t) => {
    let n = vt(),
        { params: r, el: i, wrapperEl: a, device: o } = e,
        s = !!r.nested,
        c = t === `on` ? `addEventListener` : `removeEventListener`,
        l = t
    !i ||
        typeof i == `string` ||
        (n[c](`touchstart`, e.onDocumentTouchStart, {
            passive: !1,
            capture: s,
        }),
        i[c](`touchstart`, e.onTouchStart, { passive: !1 }),
        i[c](`pointerdown`, e.onTouchStart, { passive: !1 }),
        n[c](`touchmove`, e.onTouchMove, { passive: !1, capture: s }),
        n[c](`pointermove`, e.onTouchMove, { passive: !1, capture: s }),
        n[c](`touchend`, e.onTouchEnd, { passive: !0 }),
        n[c](`pointerup`, e.onTouchEnd, { passive: !0 }),
        n[c](`pointercancel`, e.onTouchEnd, { passive: !0 }),
        n[c](`touchcancel`, e.onTouchEnd, { passive: !0 }),
        n[c](`pointerout`, e.onTouchEnd, { passive: !0 }),
        n[c](`pointerleave`, e.onTouchEnd, { passive: !0 }),
        n[c](`contextmenu`, e.onTouchEnd, { passive: !0 }),
        (r.preventClicks || r.preventClicksPropagation) &&
            i[c](`click`, e.onClick, !0),
        r.cssMode && a[c](`scroll`, e.onScroll),
        r.updateOnWindowResize
            ? e[l](
                  o.ios || o.android
                      ? `resize orientationchange observerUpdate`
                      : `resize observerUpdate`,
                  Zn,
                  !0,
              )
            : e[l](`observerUpdate`, Zn, !0),
        i[c](`load`, e.onLoad, { capture: !0 }))
}
function rr() {
    let e = this,
        { params: t } = e
    ;((e.onTouchStart = Jn.bind(e)),
        (e.onTouchMove = Yn.bind(e)),
        (e.onTouchEnd = Xn.bind(e)),
        (e.onDocumentTouchStart = tr.bind(e)),
        t.cssMode && (e.onScroll = $n.bind(e)),
        (e.onClick = Qn.bind(e)),
        (e.onLoad = er.bind(e)),
        nr(e, `on`))
}
function ir() {
    nr(this, `off`)
}
var ar = { attachEvents: rr, detachEvents: ir },
    or = (e, t) => e.grid && t.grid && t.grid.rows > 1
function sr() {
    let e = this,
        { realIndex: t, initialized: n, params: r, el: i } = e,
        a = r.breakpoints
    if (!a || (a && Object.keys(a).length === 0)) return
    let o = vt(),
        s =
            r.breakpointsBase === `window` || !r.breakpointsBase
                ? r.breakpointsBase
                : `container`,
        c =
            [`window`, `container`].includes(r.breakpointsBase) ||
            !r.breakpointsBase
                ? e.el
                : o.querySelector(r.breakpointsBase),
        l = e.getBreakpoint(a, s, c)
    if (!l || e.currentBreakpoint === l) return
    let u = (l in a ? a[l] : void 0) || e.originalParams,
        d = or(e, r),
        f = or(e, u),
        p = e.params.grabCursor,
        m = u.grabCursor,
        h = r.enabled
    ;(d && !f
        ? (i.classList.remove(
              `${r.containerModifierClass}grid`,
              `${r.containerModifierClass}grid-column`,
          ),
          e.emitContainerClasses())
        : !d &&
          f &&
          (i.classList.add(`${r.containerModifierClass}grid`),
          ((u.grid.fill && u.grid.fill === `column`) ||
              (!u.grid.fill && r.grid.fill === `column`)) &&
              i.classList.add(`${r.containerModifierClass}grid-column`),
          e.emitContainerClasses()),
        p && !m ? e.unsetGrabCursor() : !p && m && e.setGrabCursor(),
        [`navigation`, `pagination`, `scrollbar`].forEach((t) => {
            if (u[t] === void 0) return
            let n = r[t] && r[t].enabled,
                i = u[t] && u[t].enabled
            ;(n && !i && e[t].disable(), !n && i && e[t].enable())
        }))
    let g = u.direction && u.direction !== r.direction,
        _ = r.loop && (u.slidesPerView !== r.slidesPerView || g),
        v = r.loop
    ;(g && n && e.changeDirection(), Ot(e.params, u))
    let y = e.params.enabled,
        b = e.params.loop
    ;(Object.assign(e, {
        allowTouchMove: e.params.allowTouchMove,
        allowSlideNext: e.params.allowSlideNext,
        allowSlidePrev: e.params.allowSlidePrev,
    }),
        h && !y ? e.disable() : !h && y && e.enable(),
        (e.currentBreakpoint = l),
        e.emit(`_beforeBreakpoint`, u),
        n &&
            (_
                ? (e.loopDestroy(), e.loopCreate(t), e.updateSlides())
                : !v && b
                  ? (e.loopCreate(t), e.updateSlides())
                  : v && !b && e.loopDestroy()),
        e.emit(`breakpoint`, u))
}
function cr(e, t = `window`, n) {
    if (!e || (t === `container` && !n)) return
    let r = !1,
        i = Q(),
        a = t === `window` ? i.innerHeight : n.clientHeight,
        o = Object.keys(e).map((e) =>
            typeof e == `string` && e.indexOf(`@`) === 0
                ? { value: a * parseFloat(e.substr(1)), point: e }
                : { value: e, point: e },
        )
    o.sort((e, t) => parseInt(e.value, 10) - parseInt(t.value, 10))
    for (let e = 0; e < o.length; e += 1) {
        let { point: a, value: s } = o[e]
        t === `window`
            ? i.matchMedia(`(min-width: ${s}px)`).matches && (r = a)
            : s <= n.clientWidth && (r = a)
    }
    return r || `max`
}
var lr = { setBreakpoint: sr, getBreakpoint: cr }
function ur(e, t) {
    let n = []
    return (
        e.forEach((e) => {
            typeof e == `object`
                ? Object.keys(e).forEach((r) => {
                      e[r] && n.push(t + r)
                  })
                : typeof e == `string` && n.push(t + e)
        }),
        n
    )
}
function dr() {
    let e = this,
        { classNames: t, params: n, rtl: r, el: i, device: a } = e,
        o = ur(
            [
                `initialized`,
                n.direction,
                { 'free-mode': e.params.freeMode && n.freeMode.enabled },
                { autoheight: n.autoHeight },
                { rtl: r },
                { grid: n.grid && n.grid.rows > 1 },
                {
                    'grid-column':
                        n.grid && n.grid.rows > 1 && n.grid.fill === `column`,
                },
                { android: a.android },
                { ios: a.ios },
                { 'css-mode': n.cssMode },
                { centered: n.cssMode && n.centeredSlides },
                { 'watch-progress': n.watchSlidesProgress },
            ],
            n.containerModifierClass,
        )
    ;(t.push(...o), i.classList.add(...t), e.emitContainerClasses())
}
function fr() {
    let e = this,
        { el: t, classNames: n } = e
    !t ||
        typeof t == `string` ||
        (t.classList.remove(...n), e.emitContainerClasses())
}
var pr = { addClasses: dr, removeClasses: fr }
function mr() {
    let e = this,
        { isLocked: t, params: n } = e,
        { slidesOffsetBefore: r } = n
    if (r) {
        let t = e.slides.length - 1,
            n = e.slidesGrid[t] + e.slidesSizesGrid[t] + r * 2
        e.isLocked = e.size > n
    } else e.isLocked = e.snapGrid.length === 1
    ;(n.allowSlideNext === !0 && (e.allowSlideNext = !e.isLocked),
        n.allowSlidePrev === !0 && (e.allowSlidePrev = !e.isLocked),
        t && t !== e.isLocked && (e.isEnd = !1),
        t !== e.isLocked && e.emit(e.isLocked ? `lock` : `unlock`))
}
var hr = { checkOverflow: mr },
    gr = {
        init: !0,
        direction: `horizontal`,
        oneWayMovement: !1,
        swiperElementNodeName: `SWIPER-CONTAINER`,
        touchEventsTarget: `wrapper`,
        initialSlide: 0,
        speed: 300,
        cssMode: !1,
        updateOnWindowResize: !0,
        resizeObserver: !0,
        nested: !1,
        createElements: !1,
        eventsPrefix: `swiper`,
        enabled: !0,
        focusableElements: `input, select, option, textarea, button, video, label`,
        width: null,
        height: null,
        preventInteractionOnTransition: !1,
        userAgent: null,
        url: null,
        edgeSwipeDetection: !1,
        edgeSwipeThreshold: 20,
        autoHeight: !1,
        setWrapperSize: !1,
        virtualTranslate: !1,
        effect: `slide`,
        breakpoints: void 0,
        breakpointsBase: `window`,
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        slidesPerGroupSkip: 0,
        slidesPerGroupAuto: !1,
        centeredSlides: !1,
        centeredSlidesBounds: !1,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        normalizeSlideIndex: !0,
        centerInsufficientSlides: !1,
        snapToSlideEdge: !1,
        watchOverflow: !0,
        roundLengths: !1,
        touchRatio: 1,
        touchAngle: 45,
        simulateTouch: !0,
        shortSwipes: !0,
        longSwipes: !0,
        longSwipesRatio: 0.5,
        longSwipesMs: 300,
        followFinger: !0,
        allowTouchMove: !0,
        threshold: 5,
        touchMoveStopPropagation: !1,
        touchStartPreventDefault: !0,
        touchStartForcePreventDefault: !1,
        touchReleaseOnEdges: !1,
        uniqueNavElements: !0,
        resistance: !0,
        resistanceRatio: 0.85,
        watchSlidesProgress: !1,
        grabCursor: !1,
        preventClicks: !0,
        preventClicksPropagation: !0,
        slideToClickedSlide: !1,
        loop: !1,
        loopAddBlankSlides: !0,
        loopAdditionalSlides: 0,
        loopPreventsSliding: !0,
        rewind: !1,
        allowSlidePrev: !0,
        allowSlideNext: !0,
        swipeHandler: null,
        noSwiping: !0,
        noSwipingClass: `swiper-no-swiping`,
        noSwipingSelector: null,
        passiveListeners: !0,
        maxBackfaceHiddenSlides: 10,
        containerModifierClass: `swiper-`,
        slideClass: `swiper-slide`,
        slideBlankClass: `swiper-slide-blank`,
        slideActiveClass: `swiper-slide-active`,
        slideVisibleClass: `swiper-slide-visible`,
        slideFullyVisibleClass: `swiper-slide-fully-visible`,
        slideNextClass: `swiper-slide-next`,
        slidePrevClass: `swiper-slide-prev`,
        wrapperClass: `swiper-wrapper`,
        lazyPreloaderClass: `swiper-lazy-preloader`,
        lazyPreloadPrevNext: 0,
        runCallbacksOnInit: !0,
        _emitClasses: !1,
    }
function _r(e, t) {
    return function (n = {}) {
        let r = Object.keys(n)[0],
            i = n[r]
        if (typeof i != `object` || !i) {
            Ot(t, n)
            return
        }
        if (
            (e[r] === !0 && (e[r] = { enabled: !0 }),
            r === `navigation` &&
                e[r] &&
                e[r].enabled &&
                !e[r].prevEl &&
                !e[r].nextEl &&
                (e[r].auto = !0),
            [`pagination`, `scrollbar`].indexOf(r) >= 0 &&
                e[r] &&
                e[r].enabled &&
                !e[r].el &&
                (e[r].auto = !0),
            !(r in e && `enabled` in i))
        ) {
            Ot(t, n)
            return
        }
        ;(typeof e[r] == `object` &&
            !(`enabled` in e[r]) &&
            (e[r].enabled = !0),
            e[r] || (e[r] = { enabled: !1 }),
            Ot(t, n))
    }
}
var vr = {
        eventsEmitter: nn,
        update: yn,
        translate: Tn,
        transition: An,
        slide: Rn,
        loop: Hn,
        grabCursor: Gn,
        events: ar,
        breakpoints: lr,
        checkOverflow: hr,
        classes: pr,
    },
    yr = {},
    br = class e {
        constructor(...t) {
            let n, r
            ;(t.length === 1 &&
            t[0].constructor &&
            Object.prototype.toString.call(t[0]).slice(8, -1) === `Object`
                ? (r = t[0])
                : ([n, r] = t),
                (r ||= {}),
                (r = Ot({}, r)),
                n && !r.el && (r.el = n))
            let i = vt()
            if (
                r.el &&
                typeof r.el == `string` &&
                i.querySelectorAll(r.el).length > 1
            ) {
                let t = []
                return (
                    i.querySelectorAll(r.el).forEach((n) => {
                        let i = Ot({}, r, { el: n })
                        t.push(new e(i))
                    }),
                    t
                )
            }
            let a = this
            ;((a.__swiper__ = !0),
                (a.support = qt()),
                (a.device = Xt({ userAgent: r.userAgent })),
                (a.browser = $t()),
                (a.eventsListeners = {}),
                (a.eventsAnyListeners = []),
                (a.modules = [...a.__modules__]),
                r.modules &&
                    Array.isArray(r.modules) &&
                    r.modules.forEach((e) => {
                        typeof e == `function` &&
                            a.modules.indexOf(e) < 0 &&
                            a.modules.push(e)
                    }))
            let o = {}
            return (
                a.modules.forEach((e) => {
                    e({
                        params: r,
                        swiper: a,
                        extendParams: _r(r, o),
                        on: a.on.bind(a),
                        once: a.once.bind(a),
                        off: a.off.bind(a),
                        emit: a.emit.bind(a),
                    })
                }),
                (a.params = Ot({}, Ot({}, gr, o), yr, r)),
                (a.originalParams = Ot({}, a.params)),
                (a.passedParams = Ot({}, r)),
                a.params &&
                    a.params.on &&
                    Object.keys(a.params.on).forEach((e) => {
                        a.on(e, a.params.on[e])
                    }),
                a.params && a.params.onAny && a.onAny(a.params.onAny),
                Object.assign(a, {
                    enabled: a.params.enabled,
                    el: n,
                    classNames: [],
                    slides: [],
                    slidesGrid: [],
                    snapGrid: [],
                    slidesSizesGrid: [],
                    isHorizontal() {
                        return a.params.direction === `horizontal`
                    },
                    isVertical() {
                        return a.params.direction === `vertical`
                    },
                    activeIndex: 0,
                    realIndex: 0,
                    isBeginning: !0,
                    isEnd: !1,
                    translate: 0,
                    previousTranslate: 0,
                    progress: 0,
                    velocity: 0,
                    animating: !1,
                    cssOverflowAdjustment() {
                        return Math.trunc(this.translate / 2 ** 23) * 2 ** 23
                    },
                    allowSlideNext: a.params.allowSlideNext,
                    allowSlidePrev: a.params.allowSlidePrev,
                    touchEventsData: {
                        isTouched: void 0,
                        isMoved: void 0,
                        allowTouchCallbacks: void 0,
                        touchStartTime: void 0,
                        isScrolling: void 0,
                        currentTranslate: void 0,
                        startTranslate: void 0,
                        allowThresholdMove: void 0,
                        focusableElements: a.params.focusableElements,
                        lastClickTime: 0,
                        clickTimeout: void 0,
                        velocities: [],
                        allowMomentumBounce: void 0,
                        startMoving: void 0,
                        pointerId: null,
                        touchId: null,
                    },
                    allowClick: !0,
                    allowTouchMove: a.params.allowTouchMove,
                    touches: {
                        startX: 0,
                        startY: 0,
                        currentX: 0,
                        currentY: 0,
                        diff: 0,
                    },
                    imagesToLoad: [],
                    imagesLoaded: 0,
                }),
                a.emit(`_swiper`),
                a.params.init && a.init(),
                a
            )
        }
        getDirectionLabel(e) {
            return this.isHorizontal()
                ? e
                : {
                      width: `height`,
                      'margin-top': `margin-left`,
                      'margin-bottom ': `margin-right`,
                      'margin-left': `margin-top`,
                      'margin-right': `margin-bottom`,
                      'padding-left': `padding-top`,
                      'padding-right': `padding-bottom`,
                      marginRight: `marginBottom`,
                  }[e]
        }
        getSlideIndex(e) {
            let { slidesEl: t, params: n } = this,
                r = Bt(Mt(t, `.${n.slideClass}, swiper-slide`)[0])
            return Bt(e) - r
        }
        getSlideIndexByData(e) {
            return this.getSlideIndex(
                this.slides.find(
                    (t) => t.getAttribute(`data-swiper-slide-index`) * 1 === e,
                ),
            )
        }
        getSlideIndexWhenGrid(e) {
            return (
                this.grid &&
                    this.params.grid &&
                    this.params.grid.rows > 1 &&
                    (this.params.grid.fill === `column`
                        ? (e = Math.floor(e / this.params.grid.rows))
                        : this.params.grid.fill === `row` &&
                          (e %= Math.ceil(
                              this.slides.length / this.params.grid.rows,
                          ))),
                e
            )
        }
        recalcSlides() {
            let e = this,
                { slidesEl: t, params: n } = e
            e.slides = Mt(t, `.${n.slideClass}, swiper-slide`)
        }
        enable() {
            let e = this
            e.enabled ||
                ((e.enabled = !0),
                e.params.grabCursor && e.setGrabCursor(),
                e.emit(`enable`))
        }
        disable() {
            let e = this
            e.enabled &&
                ((e.enabled = !1),
                e.params.grabCursor && e.unsetGrabCursor(),
                e.emit(`disable`))
        }
        setProgress(e, t) {
            let n = this
            e = Math.min(Math.max(e, 0), 1)
            let r = n.minTranslate(),
                i = (n.maxTranslate() - r) * e + r
            ;(n.translateTo(i, t === void 0 ? 0 : t),
                n.updateActiveIndex(),
                n.updateSlidesClasses())
        }
        emitContainerClasses() {
            let e = this
            if (!e.params._emitClasses || !e.el) return
            let t = e.el.className
                .split(` `)
                .filter(
                    (t) =>
                        t.indexOf(`swiper`) === 0 ||
                        t.indexOf(e.params.containerModifierClass) === 0,
                )
            e.emit(`_containerClasses`, t.join(` `))
        }
        getSlideClasses(e) {
            let t = this
            return t.destroyed
                ? ``
                : e.className
                      .split(` `)
                      .filter(
                          (e) =>
                              e.indexOf(`swiper-slide`) === 0 ||
                              e.indexOf(t.params.slideClass) === 0,
                      )
                      .join(` `)
        }
        emitSlidesClasses() {
            let e = this
            if (!e.params._emitClasses || !e.el) return
            let t = []
            ;(e.slides.forEach((n) => {
                let r = e.getSlideClasses(n)
                ;(t.push({ slideEl: n, classNames: r }),
                    e.emit(`_slideClass`, n, r))
            }),
                e.emit(`_slideClasses`, t))
        }
        slidesPerViewDynamic(e = `current`, t = !1) {
            let {
                    params: n,
                    slides: r,
                    slidesGrid: i,
                    slidesSizesGrid: a,
                    size: o,
                    activeIndex: s,
                } = this,
                c = 1
            if (typeof n.slidesPerView == `number`) return n.slidesPerView
            if (n.centeredSlides) {
                let e = r[s] ? Math.ceil(r[s].swiperSlideSize) : 0,
                    t
                for (let n = s + 1; n < r.length; n += 1)
                    r[n] &&
                        !t &&
                        ((e += Math.ceil(r[n].swiperSlideSize)),
                        (c += 1),
                        e > o && (t = !0))
                for (let n = s - 1; n >= 0; --n)
                    r[n] &&
                        !t &&
                        ((e += r[n].swiperSlideSize),
                        (c += 1),
                        e > o && (t = !0))
            } else if (e === `current`)
                for (let e = s + 1; e < r.length; e += 1)
                    (t ? i[e] + a[e] - i[s] < o : i[e] - i[s] < o) && (c += 1)
            else for (let e = s - 1; e >= 0; --e) i[s] - i[e] < o && (c += 1)
            return c
        }
        update() {
            let e = this
            if (!e || e.destroyed) return
            let { snapGrid: t, params: n } = e
            ;(n.breakpoints && e.setBreakpoint(),
                [...e.el.querySelectorAll(`[loading="lazy"]`)].forEach((t) => {
                    t.complete && pn(e, t)
                }),
                e.updateSize(),
                e.updateSlides(),
                e.updateProgress(),
                e.updateSlidesClasses())
            function r() {
                let t = e.rtlTranslate ? e.translate * -1 : e.translate,
                    n = Math.min(
                        Math.max(t, e.maxTranslate()),
                        e.minTranslate(),
                    )
                ;(e.setTranslate(n),
                    e.updateActiveIndex(),
                    e.updateSlidesClasses())
            }
            let i
            if (n.freeMode && n.freeMode.enabled && !n.cssMode)
                (r(), n.autoHeight && e.updateAutoHeight())
            else {
                if (
                    (n.slidesPerView === `auto` || n.slidesPerView > 1) &&
                    e.isEnd &&
                    !n.centeredSlides
                ) {
                    let t =
                        e.virtual && n.virtual.enabled
                            ? e.virtual.slides
                            : e.slides
                    i = e.slideTo(t.length - 1, 0, !1, !0)
                } else i = e.slideTo(e.activeIndex, 0, !1, !0)
                i || r()
            }
            ;(n.watchOverflow && t !== e.snapGrid && e.checkOverflow(),
                e.emit(`update`))
        }
        changeDirection(e, t = !0) {
            let n = this,
                r = n.params.direction
            return (
                (e ||= r === `horizontal` ? `vertical` : `horizontal`),
                e === r || (e !== `horizontal` && e !== `vertical`)
                    ? n
                    : (n.el.classList.remove(
                          `${n.params.containerModifierClass}${r}`,
                      ),
                      n.el.classList.add(
                          `${n.params.containerModifierClass}${e}`,
                      ),
                      n.emitContainerClasses(),
                      (n.params.direction = e),
                      n.slides.forEach((t) => {
                          e === `vertical`
                              ? (t.style.width = ``)
                              : (t.style.height = ``)
                      }),
                      n.emit(`changeDirection`),
                      t && n.update(),
                      n)
            )
        }
        changeLanguageDirection(e) {
            let t = this
            ;(t.rtl && e === `rtl`) ||
                (!t.rtl && e === `ltr`) ||
                ((t.rtl = e === `rtl`),
                (t.rtlTranslate = t.params.direction === `horizontal` && t.rtl),
                t.rtl
                    ? (t.el.classList.add(
                          `${t.params.containerModifierClass}rtl`,
                      ),
                      (t.el.dir = `rtl`))
                    : (t.el.classList.remove(
                          `${t.params.containerModifierClass}rtl`,
                      ),
                      (t.el.dir = `ltr`)),
                t.update())
        }
        mount(e) {
            let t = this
            if (t.mounted) return !0
            let n = e || t.params.el
            if ((typeof n == `string` && (n = document.querySelector(n)), !n))
                return !1
            ;((n.swiper = t),
                n.parentNode &&
                    n.parentNode.host &&
                    n.parentNode.host.nodeName ===
                        t.params.swiperElementNodeName.toUpperCase() &&
                    (t.isElement = !0))
            let r = () =>
                    `.${(t.params.wrapperClass || ``).trim().split(` `).join(`.`)}`,
                i =
                    n && n.shadowRoot && n.shadowRoot.querySelector
                        ? n.shadowRoot.querySelector(r())
                        : Mt(n, r())[0]
            return (
                !i &&
                    t.params.createElements &&
                    ((i = It(`div`, t.params.wrapperClass)),
                    n.append(i),
                    Mt(n, `.${t.params.slideClass}`).forEach((e) => {
                        i.append(e)
                    })),
                Object.assign(t, {
                    el: n,
                    wrapperEl: i,
                    slidesEl:
                        t.isElement && !n.parentNode.host.slideSlots
                            ? n.parentNode.host
                            : i,
                    hostEl: t.isElement ? n.parentNode.host : n,
                    mounted: !0,
                    rtl:
                        n.dir.toLowerCase() === `rtl` ||
                        zt(n, `direction`) === `rtl`,
                    rtlTranslate:
                        t.params.direction === `horizontal` &&
                        (n.dir.toLowerCase() === `rtl` ||
                            zt(n, `direction`) === `rtl`),
                    wrongRTL: zt(i, `display`) === `-webkit-box`,
                }),
                !0
            )
        }
        init(e) {
            let t = this
            if (t.initialized || t.mount(e) === !1) return t
            ;(t.emit(`beforeInit`),
                t.params.breakpoints && t.setBreakpoint(),
                t.addClasses(),
                t.updateSize(),
                t.updateSlides(),
                t.params.watchOverflow && t.checkOverflow(),
                t.params.grabCursor && t.enabled && t.setGrabCursor(),
                t.params.loop && t.virtual && t.params.virtual.enabled
                    ? t.slideTo(
                          t.params.initialSlide + t.virtual.slidesBefore,
                          0,
                          t.params.runCallbacksOnInit,
                          !1,
                          !0,
                      )
                    : t.slideTo(
                          t.params.initialSlide,
                          0,
                          t.params.runCallbacksOnInit,
                          !1,
                          !0,
                      ),
                t.params.loop && t.loopCreate(void 0, !0),
                t.attachEvents())
            let n = [...t.el.querySelectorAll(`[loading="lazy"]`)]
            return (
                t.isElement &&
                    n.push(...t.hostEl.querySelectorAll(`[loading="lazy"]`)),
                n.forEach((e) => {
                    e.complete
                        ? pn(t, e)
                        : e.addEventListener(`load`, (e) => {
                              pn(t, e.target)
                          })
                }),
                hn(t),
                (t.initialized = !0),
                hn(t),
                t.emit(`init`),
                t.emit(`afterInit`),
                t
            )
        }
        destroy(e = !0, t = !0) {
            let n = this,
                { params: r, el: i, wrapperEl: a, slides: o } = n
            return n.params === void 0 || n.destroyed
                ? null
                : (n.emit(`beforeDestroy`),
                  (n.initialized = !1),
                  n.detachEvents(),
                  r.loop && n.loopDestroy(),
                  t &&
                      (n.removeClasses(),
                      i && typeof i != `string` && i.removeAttribute(`style`),
                      a && a.removeAttribute(`style`),
                      o &&
                          o.length &&
                          o.forEach((e) => {
                              ;(e.classList.remove(
                                  r.slideVisibleClass,
                                  r.slideFullyVisibleClass,
                                  r.slideActiveClass,
                                  r.slideNextClass,
                                  r.slidePrevClass,
                              ),
                                  e.removeAttribute(`style`),
                                  e.removeAttribute(`data-swiper-slide-index`))
                          })),
                  n.emit(`destroy`),
                  Object.keys(n.eventsListeners).forEach((e) => {
                      n.off(e)
                  }),
                  e !== !1 &&
                      (n.el && typeof n.el != `string` && (n.el.swiper = null),
                      xt(n)),
                  (n.destroyed = !0),
                  null)
        }
        static extendDefaults(e) {
            Ot(yr, e)
        }
        static get extendedDefaults() {
            return yr
        }
        static get defaults() {
            return gr
        }
        static installModule(t) {
            e.prototype.__modules__ || (e.prototype.__modules__ = [])
            let n = e.prototype.__modules__
            typeof t == `function` && n.indexOf(t) < 0 && n.push(t)
        }
        static use(t) {
            return Array.isArray(t)
                ? (t.forEach((t) => e.installModule(t)), e)
                : (e.installModule(t), e)
        }
    }
;(Object.keys(vr).forEach((e) => {
    Object.keys(vr[e]).forEach((t) => {
        br.prototype[t] = vr[e][t]
    })
}),
    br.use([en, tn]))
function xr({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = Q()
    ;(t({
        mousewheel: {
            enabled: !1,
            releaseOnEdges: !1,
            invert: !1,
            forceToAxis: !1,
            sensitivity: 1,
            eventsTarget: `container`,
            thresholdDelta: null,
            thresholdTime: null,
            noMousewheelClass: `swiper-no-mousewheel`,
        },
    }),
        (e.mousewheel = { enabled: !1 }))
    let a,
        o = Ct(),
        s,
        c = []
    function l(e) {
        let t = 0,
            n = 0,
            r = 0,
            i = 0
        return (
            `detail` in e && (n = e.detail),
            `wheelDelta` in e && (n = -e.wheelDelta / 120),
            `wheelDeltaY` in e && (n = -e.wheelDeltaY / 120),
            `wheelDeltaX` in e && (t = -e.wheelDeltaX / 120),
            `axis` in e && e.axis === e.HORIZONTAL_AXIS && ((t = n), (n = 0)),
            (r = t * 10),
            (i = n * 10),
            `deltaY` in e && (i = e.deltaY),
            `deltaX` in e && (r = e.deltaX),
            e.shiftKey && !r && ((r = i), (i = 0)),
            (r || i) &&
                e.deltaMode &&
                (e.deltaMode === 1
                    ? ((r *= 40), (i *= 40))
                    : ((r *= 800), (i *= 800))),
            r && !t && (t = r < 1 ? -1 : 1),
            i && !n && (n = i < 1 ? -1 : 1),
            { spinX: t, spinY: n, pixelX: r, pixelY: i }
        )
    }
    function u() {
        e.enabled && (e.mouseEntered = !0)
    }
    function d() {
        e.enabled && (e.mouseEntered = !1)
    }
    function f(t) {
        return (e.params.mousewheel.thresholdDelta &&
            t.delta < e.params.mousewheel.thresholdDelta) ||
            (e.params.mousewheel.thresholdTime &&
                Ct() - o < e.params.mousewheel.thresholdTime)
            ? !1
            : t.delta >= 6 && Ct() - o < 60
              ? !0
              : (t.direction < 0
                    ? (!e.isEnd || e.params.loop) &&
                      !e.animating &&
                      (e.slideNext(), r(`scroll`, t.raw))
                    : (!e.isBeginning || e.params.loop) &&
                      !e.animating &&
                      (e.slidePrev(), r(`scroll`, t.raw)),
                (o = new i.Date().getTime()),
                !1)
    }
    function p(t) {
        let n = e.params.mousewheel
        if (t.direction < 0) {
            if (e.isEnd && !e.params.loop && n.releaseOnEdges) return !0
        } else if (e.isBeginning && !e.params.loop && n.releaseOnEdges)
            return !0
        return !1
    }
    function m(t) {
        let n = t,
            i = !0
        if (
            !e.enabled ||
            t.target.closest(`.${e.params.mousewheel.noMousewheelClass}`)
        )
            return
        let o = e.params.mousewheel
        e.params.cssMode && n.preventDefault()
        let u = e.el
        e.params.mousewheel.eventsTarget !== `container` &&
            (u = document.querySelector(e.params.mousewheel.eventsTarget))
        let d = u && u.contains(n.target)
        if (!e.mouseEntered && !d && !o.releaseOnEdges) return !0
        n.originalEvent && (n = n.originalEvent)
        let m = 0,
            h = e.rtlTranslate ? -1 : 1,
            g = l(n)
        if (o.forceToAxis)
            if (e.isHorizontal())
                if (Math.abs(g.pixelX) > Math.abs(g.pixelY)) m = -g.pixelX * h
                else return !0
            else if (Math.abs(g.pixelY) > Math.abs(g.pixelX)) m = -g.pixelY
            else return !0
        else
            m =
                Math.abs(g.pixelX) > Math.abs(g.pixelY)
                    ? -g.pixelX * h
                    : -g.pixelY
        if (m === 0) return !0
        o.invert && (m = -m)
        let _ = e.getTranslate() + m * o.sensitivity
        if (
            (_ >= e.minTranslate() && (_ = e.minTranslate()),
            _ <= e.maxTranslate() && (_ = e.maxTranslate()),
            (i = e.params.loop
                ? !0
                : !(_ === e.minTranslate() || _ === e.maxTranslate())),
            i && e.params.nested && n.stopPropagation(),
            !e.params.freeMode || !e.params.freeMode.enabled)
        ) {
            let e = {
                time: Ct(),
                delta: Math.abs(m),
                direction: Math.sign(m),
                raw: t,
            }
            c.length >= 2 && c.shift()
            let n = c.length ? c[c.length - 1] : void 0
            if (
                (c.push(e),
                n
                    ? (e.direction !== n.direction ||
                          e.delta > n.delta ||
                          e.time > n.time + 150) &&
                      f(e)
                    : f(e),
                p(e))
            )
                return !0
        } else {
            let t = { time: Ct(), delta: Math.abs(m), direction: Math.sign(m) },
                i =
                    s &&
                    t.time < s.time + 500 &&
                    t.delta <= s.delta &&
                    t.direction === s.direction
            if (!i) {
                s = void 0
                let l = e.getTranslate() + m * o.sensitivity,
                    u = e.isBeginning,
                    d = e.isEnd
                if (
                    (l >= e.minTranslate() && (l = e.minTranslate()),
                    l <= e.maxTranslate() && (l = e.maxTranslate()),
                    e.setTransition(0),
                    e.setTranslate(l),
                    e.updateProgress(),
                    e.updateActiveIndex(),
                    e.updateSlidesClasses(),
                    ((!u && e.isBeginning) || (!d && e.isEnd)) &&
                        e.updateSlidesClasses(),
                    e.params.loop &&
                        e.loopFix({
                            direction: t.direction < 0 ? `next` : `prev`,
                            byMousewheel: !0,
                        }),
                    e.params.freeMode.sticky)
                ) {
                    ;(clearTimeout(a),
                        (a = void 0),
                        c.length >= 15 && c.shift())
                    let n = c.length ? c[c.length - 1] : void 0,
                        r = c[0]
                    if (
                        (c.push(t),
                        n && (t.delta > n.delta || t.direction !== n.direction))
                    )
                        c.splice(0)
                    else if (
                        c.length >= 15 &&
                        t.time - r.time < 500 &&
                        r.delta - t.delta >= 1 &&
                        t.delta <= 6
                    ) {
                        let n = m > 0 ? 0.8 : 0.2
                        ;((s = t),
                            c.splice(0),
                            (a = St(() => {
                                e.destroyed ||
                                    !e.params ||
                                    e.slideToClosest(
                                        e.params.speed,
                                        !0,
                                        void 0,
                                        n,
                                    )
                            }, 0)))
                    }
                    a ||= St(() => {
                        e.destroyed ||
                            !e.params ||
                            ((s = t),
                            c.splice(0),
                            e.slideToClosest(e.params.speed, !0, void 0, 0.5))
                    }, 500)
                }
                if (
                    (i || r(`scroll`, n),
                    e.params.autoplay &&
                        e.params.autoplay.disableOnInteraction &&
                        e.autoplay.stop(),
                    o.releaseOnEdges &&
                        (l === e.minTranslate() || l === e.maxTranslate()))
                )
                    return !0
            }
        }
        return (
            n.preventDefault ? n.preventDefault() : (n.returnValue = !1),
            !1
        )
    }
    function h(t) {
        let n = e.el
        ;(e.params.mousewheel.eventsTarget !== `container` &&
            (n = document.querySelector(e.params.mousewheel.eventsTarget)),
            n[t](`mouseenter`, u),
            n[t](`mouseleave`, d),
            n[t](`wheel`, m))
    }
    function g() {
        return e.params.cssMode
            ? (e.wrapperEl.removeEventListener(`wheel`, m), !0)
            : e.mousewheel.enabled
              ? !1
              : (h(`addEventListener`), (e.mousewheel.enabled = !0), !0)
    }
    function _() {
        return e.params.cssMode
            ? (e.wrapperEl.addEventListener(event, m), !0)
            : e.mousewheel.enabled
              ? (h(`removeEventListener`), (e.mousewheel.enabled = !1), !0)
              : !1
    }
    ;(n(`init`, () => {
        ;(!e.params.mousewheel.enabled && e.params.cssMode && _(),
            e.params.mousewheel.enabled && g())
    }),
        n(`destroy`, () => {
            ;(e.params.cssMode && g(), e.mousewheel.enabled && _())
        }),
        Object.assign(e.mousewheel, { enable: g, disable: _ }))
}
function Sr(e, t, n, r) {
    return (
        e.params.createElements &&
            Object.keys(r).forEach((i) => {
                if (!n[i] && n.auto === !0) {
                    let a = Mt(e.el, `.${r[i]}`)[0]
                    ;(a ||
                        ((a = It(`div`, r[i])),
                        (a.className = r[i]),
                        e.el.append(a)),
                        (n[i] = a),
                        (t[i] = a))
                }
            }),
        n
    )
}
var Cr = `<svg class="swiper-navigation-icon" width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.38296 20.0762C0.111788 19.805 0.111788 19.3654 0.38296 19.0942L9.19758 10.2796L0.38296 1.46497C0.111788 1.19379 0.111788 0.754138 0.38296 0.482966C0.654131 0.211794 1.09379 0.211794 1.36496 0.482966L10.4341 9.55214C10.8359 9.9539 10.8359 10.6053 10.4341 11.007L1.36496 20.0762C1.09379 20.3474 0.654131 20.3474 0.38296 20.0762Z" fill="currentColor"/></svg>`
function wr({ swiper: e, extendParams: t, on: n, emit: r }) {
    ;(t({
        navigation: {
            nextEl: null,
            prevEl: null,
            addIcons: !0,
            hideOnClick: !1,
            disabledClass: `swiper-button-disabled`,
            hiddenClass: `swiper-button-hidden`,
            lockClass: `swiper-button-lock`,
            navigationDisabledClass: `swiper-navigation-disabled`,
        },
    }),
        (e.navigation = { nextEl: null, prevEl: null, arrowSvg: Cr }))
    function i(t) {
        let n
        return t &&
            typeof t == `string` &&
            e.isElement &&
            ((n = e.el.querySelector(t) || e.hostEl.querySelector(t)), n)
            ? n
            : (t &&
                  (typeof t == `string` &&
                      (n = [...document.querySelectorAll(t)]),
                  e.params.uniqueNavElements &&
                  typeof t == `string` &&
                  n &&
                  n.length > 1 &&
                  e.el.querySelectorAll(t).length === 1
                      ? (n = e.el.querySelector(t))
                      : n && n.length === 1 && (n = n[0])),
              t && !n ? t : n)
    }
    function a(t, n) {
        let r = e.params.navigation
        ;((t = $(t)),
            t.forEach((t) => {
                t &&
                    (t.classList[n ? `add` : `remove`](
                        ...r.disabledClass.split(` `),
                    ),
                    t.tagName === `BUTTON` && (t.disabled = n),
                    e.params.watchOverflow &&
                        e.enabled &&
                        t.classList[e.isLocked ? `add` : `remove`](r.lockClass))
            }))
    }
    function o() {
        let { nextEl: t, prevEl: n } = e.navigation
        if (e.params.loop) {
            ;(a(n, !1), a(t, !1))
            return
        }
        ;(a(n, e.isBeginning && !e.params.rewind),
            a(t, e.isEnd && !e.params.rewind))
    }
    function s(t) {
        ;(t.preventDefault(),
            !(e.isBeginning && !e.params.loop && !e.params.rewind) &&
                (e.slidePrev(), r(`navigationPrev`)))
    }
    function c(t) {
        ;(t.preventDefault(),
            !(e.isEnd && !e.params.loop && !e.params.rewind) &&
                (e.slideNext(), r(`navigationNext`)))
    }
    function l() {
        let t = e.params.navigation
        if (
            ((e.params.navigation = Sr(
                e,
                e.originalParams.navigation,
                e.params.navigation,
                { nextEl: `swiper-button-next`, prevEl: `swiper-button-prev` },
            )),
            !(t.nextEl || t.prevEl))
        )
            return
        let n = i(t.nextEl),
            r = i(t.prevEl)
        ;(Object.assign(e.navigation, { nextEl: n, prevEl: r }),
            (n = $(n)),
            (r = $(r)))
        let a = (n, r) => {
            if (n) {
                if (
                    t.addIcons &&
                    n.matches(`.swiper-button-next,.swiper-button-prev`) &&
                    !n.querySelector(`svg`)
                ) {
                    let e = document.createElement(`div`)
                    ;(Wt(e, Cr),
                        n.appendChild(e.querySelector(`svg`)),
                        e.remove())
                }
                n.addEventListener(`click`, r === `next` ? c : s)
            }
            !e.enabled && n && n.classList.add(...t.lockClass.split(` `))
        }
        ;(n.forEach((e) => a(e, `next`)), r.forEach((e) => a(e, `prev`)))
    }
    function u() {
        let { nextEl: t, prevEl: n } = e.navigation
        ;((t = $(t)), (n = $(n)))
        let r = (t, n) => {
            ;(t.removeEventListener(`click`, n === `next` ? c : s),
                t.classList.remove(
                    ...e.params.navigation.disabledClass.split(` `),
                ))
        }
        ;(t.forEach((e) => r(e, `next`)), n.forEach((e) => r(e, `prev`)))
    }
    ;(n(`init`, () => {
        e.params.navigation.enabled === !1 ? f() : (l(), o())
    }),
        n(`toEdge fromEdge lock unlock`, () => {
            o()
        }),
        n(`destroy`, () => {
            u()
        }),
        n(`enable disable`, () => {
            let { nextEl: t, prevEl: n } = e.navigation
            if (((t = $(t)), (n = $(n)), e.enabled)) {
                o()
                return
            }
            ;[...t, ...n]
                .filter((e) => !!e)
                .forEach((t) => t.classList.add(e.params.navigation.lockClass))
        }),
        n(`click`, (t, n) => {
            let { nextEl: i, prevEl: a } = e.navigation
            ;((i = $(i)), (a = $(a)))
            let o = n.target,
                s = a.includes(o) || i.includes(o)
            if (e.isElement && !s) {
                let e = n.path || (n.composedPath && n.composedPath())
                e && (s = e.find((e) => i.includes(e) || a.includes(e)))
            }
            if (e.params.navigation.hideOnClick && !s) {
                if (
                    e.pagination &&
                    e.params.pagination &&
                    e.params.pagination.clickable &&
                    (e.pagination.el === o || e.pagination.el.contains(o))
                )
                    return
                let t
                ;(i.length
                    ? (t = i[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      ))
                    : a.length &&
                      (t = a[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      )),
                    r(t === !0 ? `navigationShow` : `navigationHide`),
                    [...i, ...a]
                        .filter((e) => !!e)
                        .forEach((t) =>
                            t.classList.toggle(e.params.navigation.hiddenClass),
                        ))
            }
        }))
    let d = () => {
            ;(e.el.classList.remove(
                ...e.params.navigation.navigationDisabledClass.split(` `),
            ),
                l(),
                o())
        },
        f = () => {
            ;(e.el.classList.add(
                ...e.params.navigation.navigationDisabledClass.split(` `),
            ),
                u())
        }
    Object.assign(e.navigation, {
        enable: d,
        disable: f,
        update: o,
        init: l,
        destroy: u,
    })
}
function Tr(e = ``) {
    return `.${e
        .trim()
        .replace(/([\.:!+\/()[\]#>~*^$|=,'"@{}\\])/g, `\\$1`)
        .replace(/ /g, `.`)}`
}
function Er({ swiper: e, extendParams: t, on: n, emit: r }) {
    let i = `swiper-pagination`
    ;(t({
        pagination: {
            el: null,
            bulletElement: `span`,
            clickable: !1,
            hideOnClick: !1,
            renderBullet: null,
            renderProgressbar: null,
            renderFraction: null,
            renderCustom: null,
            progressbarOpposite: !1,
            type: `bullets`,
            dynamicBullets: !1,
            dynamicMainBullets: 1,
            formatFractionCurrent: (e) => e,
            formatFractionTotal: (e) => e,
            bulletClass: `${i}-bullet`,
            bulletActiveClass: `${i}-bullet-active`,
            modifierClass: `${i}-`,
            currentClass: `${i}-current`,
            totalClass: `${i}-total`,
            hiddenClass: `${i}-hidden`,
            progressbarFillClass: `${i}-progressbar-fill`,
            progressbarOppositeClass: `${i}-progressbar-opposite`,
            clickableClass: `${i}-clickable`,
            lockClass: `${i}-lock`,
            horizontalClass: `${i}-horizontal`,
            verticalClass: `${i}-vertical`,
            paginationDisabledClass: `${i}-disabled`,
        },
    }),
        (e.pagination = { el: null, bullets: [] }))
    let a,
        o = 0
    function s() {
        return (
            !e.params.pagination.el ||
            !e.pagination.el ||
            (Array.isArray(e.pagination.el) && e.pagination.el.length === 0)
        )
    }
    function c(t, n) {
        let { bulletActiveClass: r } = e.params.pagination
        t &&
            ((t = t[`${n === `prev` ? `previous` : `next`}ElementSibling`]),
            t &&
                (t.classList.add(`${r}-${n}`),
                (t = t[`${n === `prev` ? `previous` : `next`}ElementSibling`]),
                t && t.classList.add(`${r}-${n}-${n}`)))
    }
    function l(e, t, n) {
        if (((e %= n), (t %= n), t === e + 1)) return `next`
        if (t === e - 1) return `previous`
    }
    function u(t) {
        let n = t.target.closest(Tr(e.params.pagination.bulletClass))
        if (!n) return
        t.preventDefault()
        let r = Bt(n) * e.params.slidesPerGroup
        if (e.params.loop) {
            if (e.realIndex === r) return
            let t = l(e.realIndex, r, e.slides.length)
            t === `next`
                ? e.slideNext()
                : t === `previous`
                  ? e.slidePrev()
                  : e.slideToLoop(r)
        } else e.slideTo(r)
    }
    function d() {
        let t = e.rtl,
            n = e.params.pagination
        if (s()) return
        let i = e.pagination.el
        i = $(i)
        let l,
            u,
            d =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.slides.length,
            f = e.params.loop
                ? Math.ceil(d / e.params.slidesPerGroup)
                : e.snapGrid.length
        if (
            (e.params.loop
                ? ((u = e.previousRealIndex || 0),
                  (l =
                      e.params.slidesPerGroup > 1
                          ? Math.floor(e.realIndex / e.params.slidesPerGroup)
                          : e.realIndex))
                : e.snapIndex === void 0
                  ? ((u = e.previousIndex || 0), (l = e.activeIndex || 0))
                  : ((l = e.snapIndex), (u = e.previousSnapIndex)),
            n.type === `bullets` &&
                e.pagination.bullets &&
                e.pagination.bullets.length > 0)
        ) {
            let r = e.pagination.bullets,
                s,
                d,
                f
            if (
                (n.dynamicBullets &&
                    ((a = Ut(r[0], e.isHorizontal() ? `width` : `height`, !0)),
                    i.forEach((t) => {
                        t.style[e.isHorizontal() ? `width` : `height`] =
                            `${a * (n.dynamicMainBullets + 4)}px`
                    }),
                    n.dynamicMainBullets > 1 &&
                        u !== void 0 &&
                        ((o += l - (u || 0)),
                        o > n.dynamicMainBullets - 1
                            ? (o = n.dynamicMainBullets - 1)
                            : o < 0 && (o = 0)),
                    (s = Math.max(l - o, 0)),
                    (d = s + (Math.min(r.length, n.dynamicMainBullets) - 1)),
                    (f = (d + s) / 2)),
                r.forEach((e) => {
                    let t = [
                        ...[
                            ``,
                            `-next`,
                            `-next-next`,
                            `-prev`,
                            `-prev-prev`,
                            `-main`,
                        ].map((e) => `${n.bulletActiveClass}${e}`),
                    ]
                        .map((e) =>
                            typeof e == `string` && e.includes(` `)
                                ? e.split(` `)
                                : e,
                        )
                        .flat()
                    e.classList.remove(...t)
                }),
                i.length > 1)
            )
                r.forEach((t) => {
                    let r = Bt(t)
                    ;(r === l
                        ? t.classList.add(...n.bulletActiveClass.split(` `))
                        : e.isElement && t.setAttribute(`part`, `bullet`),
                        n.dynamicBullets &&
                            (r >= s &&
                                r <= d &&
                                t.classList.add(
                                    ...`${n.bulletActiveClass}-main`.split(` `),
                                ),
                            r === s && c(t, `prev`),
                            r === d && c(t, `next`)))
                })
            else {
                let t = r[l]
                if (
                    (t && t.classList.add(...n.bulletActiveClass.split(` `)),
                    e.isElement &&
                        r.forEach((e, t) => {
                            e.setAttribute(
                                `part`,
                                t === l ? `bullet-active` : `bullet`,
                            )
                        }),
                    n.dynamicBullets)
                ) {
                    let e = r[s],
                        t = r[d]
                    for (let e = s; e <= d; e += 1)
                        r[e] &&
                            r[e].classList.add(
                                ...`${n.bulletActiveClass}-main`.split(` `),
                            )
                    ;(c(e, `prev`), c(t, `next`))
                }
            }
            if (n.dynamicBullets) {
                let i = Math.min(r.length, n.dynamicMainBullets + 4),
                    o = (a * i - a) / 2 - f * a,
                    s = t ? `right` : `left`
                r.forEach((t) => {
                    t.style[e.isHorizontal() ? s : `top`] = `${o}px`
                })
            }
        }
        i.forEach((t, i) => {
            if (
                (n.type === `fraction` &&
                    (t.querySelectorAll(Tr(n.currentClass)).forEach((e) => {
                        e.textContent = n.formatFractionCurrent(l + 1)
                    }),
                    t.querySelectorAll(Tr(n.totalClass)).forEach((e) => {
                        e.textContent = n.formatFractionTotal(f)
                    })),
                n.type === `progressbar`)
            ) {
                let r
                r = n.progressbarOpposite
                    ? e.isHorizontal()
                        ? `vertical`
                        : `horizontal`
                    : e.isHorizontal()
                      ? `horizontal`
                      : `vertical`
                let i = (l + 1) / f,
                    a = 1,
                    o = 1
                ;(r === `horizontal` ? (a = i) : (o = i),
                    t
                        .querySelectorAll(Tr(n.progressbarFillClass))
                        .forEach((t) => {
                            ;((t.style.transform = `translate3d(0,0,0) scaleX(${a}) scaleY(${o})`),
                                (t.style.transitionDuration = `${e.params.speed}ms`))
                        }))
            }
            ;(n.type === `custom` && n.renderCustom
                ? (Wt(t, n.renderCustom(e, l + 1, f)),
                  i === 0 && r(`paginationRender`, t))
                : (i === 0 && r(`paginationRender`, t),
                  r(`paginationUpdate`, t)),
                e.params.watchOverflow &&
                    e.enabled &&
                    t.classList[e.isLocked ? `add` : `remove`](n.lockClass))
        })
    }
    function f() {
        let t = e.params.pagination
        if (s()) return
        let n =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.grid && e.params.grid.rows > 1
                      ? e.slides.length / Math.ceil(e.params.grid.rows)
                      : e.slides.length,
            i = e.pagination.el
        i = $(i)
        let a = ``
        if (t.type === `bullets`) {
            let r = e.params.loop
                ? Math.ceil(n / e.params.slidesPerGroup)
                : e.snapGrid.length
            e.params.freeMode && e.params.freeMode.enabled && r > n && (r = n)
            for (let n = 0; n < r; n += 1)
                t.renderBullet
                    ? (a += t.renderBullet.call(e, n, t.bulletClass))
                    : (a += `<${t.bulletElement} ${e.isElement ? `part="bullet"` : ``} class="${t.bulletClass}"></${t.bulletElement}>`)
        }
        ;(t.type === `fraction` &&
            (a = t.renderFraction
                ? t.renderFraction.call(e, t.currentClass, t.totalClass)
                : `<span class="${t.currentClass}"></span> / <span class="${t.totalClass}"></span>`),
            t.type === `progressbar` &&
                (a = t.renderProgressbar
                    ? t.renderProgressbar.call(e, t.progressbarFillClass)
                    : `<span class="${t.progressbarFillClass}"></span>`),
            (e.pagination.bullets = []),
            i.forEach((n) => {
                ;(t.type !== `custom` && Wt(n, a || ``),
                    t.type === `bullets` &&
                        e.pagination.bullets.push(
                            ...n.querySelectorAll(Tr(t.bulletClass)),
                        ))
            }),
            t.type !== `custom` && r(`paginationRender`, i[0]))
    }
    function p() {
        e.params.pagination = Sr(
            e,
            e.originalParams.pagination,
            e.params.pagination,
            { el: `swiper-pagination` },
        )
        let t = e.params.pagination
        if (!t.el) return
        let n
        ;(typeof t.el == `string` &&
            e.isElement &&
            (n = e.el.querySelector(t.el)),
            !n &&
                typeof t.el == `string` &&
                (n = [...document.querySelectorAll(t.el)]),
            (n ||= t.el),
            !(!n || n.length === 0) &&
                (e.params.uniqueNavElements &&
                    typeof t.el == `string` &&
                    Array.isArray(n) &&
                    n.length > 1 &&
                    ((n = [...e.el.querySelectorAll(t.el)]),
                    n.length > 1 &&
                        (n = n.find((t) => Vt(t, `.swiper`)[0] === e.el))),
                Array.isArray(n) && n.length === 1 && (n = n[0]),
                Object.assign(e.pagination, { el: n }),
                (n = $(n)),
                n.forEach((n) => {
                    ;(t.type === `bullets` &&
                        t.clickable &&
                        n.classList.add(...(t.clickableClass || ``).split(` `)),
                        n.classList.add(t.modifierClass + t.type),
                        n.classList.add(
                            e.isHorizontal()
                                ? t.horizontalClass
                                : t.verticalClass,
                        ),
                        t.type === `bullets` &&
                            t.dynamicBullets &&
                            (n.classList.add(
                                `${t.modifierClass}${t.type}-dynamic`,
                            ),
                            (o = 0),
                            t.dynamicMainBullets < 1 &&
                                (t.dynamicMainBullets = 1)),
                        t.type === `progressbar` &&
                            t.progressbarOpposite &&
                            n.classList.add(t.progressbarOppositeClass),
                        t.clickable && n.addEventListener(`click`, u),
                        e.enabled || n.classList.add(t.lockClass))
                })))
    }
    function m() {
        let t = e.params.pagination
        if (s()) return
        let n = e.pagination.el
        ;(n &&
            ((n = $(n)),
            n.forEach((n) => {
                ;(n.classList.remove(t.hiddenClass),
                    n.classList.remove(t.modifierClass + t.type),
                    n.classList.remove(
                        e.isHorizontal() ? t.horizontalClass : t.verticalClass,
                    ),
                    t.clickable &&
                        (n.classList.remove(
                            ...(t.clickableClass || ``).split(` `),
                        ),
                        n.removeEventListener(`click`, u)))
            })),
            e.pagination.bullets &&
                e.pagination.bullets.forEach((e) =>
                    e.classList.remove(...t.bulletActiveClass.split(` `)),
                ))
    }
    ;(n(`changeDirection`, () => {
        if (!e.pagination || !e.pagination.el) return
        let t = e.params.pagination,
            { el: n } = e.pagination
        ;((n = $(n)),
            n.forEach((n) => {
                ;(n.classList.remove(t.horizontalClass, t.verticalClass),
                    n.classList.add(
                        e.isHorizontal() ? t.horizontalClass : t.verticalClass,
                    ))
            }))
    }),
        n(`init`, () => {
            e.params.pagination.enabled === !1 ? g() : (p(), f(), d())
        }),
        n(`activeIndexChange`, () => {
            e.snapIndex === void 0 && d()
        }),
        n(`snapIndexChange`, () => {
            d()
        }),
        n(`snapGridLengthChange`, () => {
            ;(f(), d())
        }),
        n(`destroy`, () => {
            m()
        }),
        n(`enable disable`, () => {
            let { el: t } = e.pagination
            t &&
                ((t = $(t)),
                t.forEach((t) =>
                    t.classList[e.enabled ? `remove` : `add`](
                        e.params.pagination.lockClass,
                    ),
                ))
        }),
        n(`lock unlock`, () => {
            d()
        }),
        n(`click`, (t, n) => {
            let i = n.target,
                a = $(e.pagination.el)
            if (
                e.params.pagination.el &&
                e.params.pagination.hideOnClick &&
                a &&
                a.length > 0 &&
                !i.classList.contains(e.params.pagination.bulletClass)
            ) {
                if (
                    e.navigation &&
                    ((e.navigation.nextEl && i === e.navigation.nextEl) ||
                        (e.navigation.prevEl && i === e.navigation.prevEl))
                )
                    return
                ;(a[0].classList.contains(e.params.pagination.hiddenClass) ===
                !0
                    ? r(`paginationShow`)
                    : r(`paginationHide`),
                    a.forEach((t) =>
                        t.classList.toggle(e.params.pagination.hiddenClass),
                    ))
            }
        }))
    let h = () => {
            e.el.classList.remove(e.params.pagination.paginationDisabledClass)
            let { el: t } = e.pagination
            ;(t &&
                ((t = $(t)),
                t.forEach((t) =>
                    t.classList.remove(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                p(),
                f(),
                d())
        },
        g = () => {
            e.el.classList.add(e.params.pagination.paginationDisabledClass)
            let { el: t } = e.pagination
            ;(t &&
                ((t = $(t)),
                t.forEach((t) =>
                    t.classList.add(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                m())
        }
    Object.assign(e.pagination, {
        enable: h,
        disable: g,
        render: f,
        update: d,
        init: p,
        destroy: m,
    })
}
function Dr({ swiper: e, extendParams: t, on: n, emit: r, params: i }) {
    ;((e.autoplay = { running: !1, paused: !1, timeLeft: 0 }),
        t({
            autoplay: {
                enabled: !1,
                delay: 3e3,
                waitForTransition: !0,
                disableOnInteraction: !1,
                stopOnLastSlide: !1,
                reverseDirection: !1,
                pauseOnMouseEnter: !1,
            },
        }))
    let a,
        o,
        s = i && i.autoplay ? i.autoplay.delay : 3e3,
        c = i && i.autoplay ? i.autoplay.delay : 3e3,
        l,
        u = new Date().getTime(),
        d,
        f,
        p,
        m,
        h,
        g
    function _(t) {
        !e ||
            e.destroyed ||
            !e.wrapperEl ||
            (t.target === e.wrapperEl &&
                (e.wrapperEl.removeEventListener(`transitionend`, _),
                !(g || (t.detail && t.detail.bySwiperTouchMove)) && T()))
    }
    let v = () => {
            if (e.destroyed || !e.autoplay.running) return
            e.autoplay.paused ? (d = !0) : (d &&= ((c = l), !1))
            let t = e.autoplay.paused ? l : u + c - new Date().getTime()
            ;((e.autoplay.timeLeft = t),
                r(`autoplayTimeLeft`, t, t / s),
                (o = requestAnimationFrame(() => {
                    v()
                })))
        },
        y = () => {
            let t
            if (
                ((t =
                    e.virtual && e.params.virtual.enabled
                        ? e.slides.find((e) =>
                              e.classList.contains(`swiper-slide-active`),
                          )
                        : e.slides[e.activeIndex]),
                t)
            )
                return parseInt(t.getAttribute(`data-swiper-autoplay`), 10)
        },
        b = () => {
            let t = e.params.autoplay.delay,
                n = y()
            return (!Number.isNaN(n) && n > 0 && (t = n), t)
        },
        x = (t) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(cancelAnimationFrame(o), v())
            let n = t
            ;(n === void 0 && ((n = b()), (s = n), (c = n)), (l = n))
            let i = e.params.speed,
                d = () => {
                    !e ||
                        e.destroyed ||
                        (e.params.autoplay.reverseDirection
                            ? !e.isBeginning || e.params.loop || e.params.rewind
                                ? (e.slidePrev(i, !0, !0), r(`autoplay`))
                                : e.params.autoplay.stopOnLastSlide ||
                                  (e.slideTo(e.slides.length - 1, i, !0, !0),
                                  r(`autoplay`))
                            : !e.isEnd || e.params.loop || e.params.rewind
                              ? (e.slideNext(i, !0, !0), r(`autoplay`))
                              : e.params.autoplay.stopOnLastSlide ||
                                (e.slideTo(0, i, !0, !0), r(`autoplay`)),
                        e.params.cssMode &&
                            ((u = new Date().getTime()),
                            requestAnimationFrame(() => {
                                x()
                            })))
                }
            return (
                n > 0
                    ? (clearTimeout(a),
                      (a = setTimeout(() => {
                          d()
                      }, n)))
                    : requestAnimationFrame(() => {
                          d()
                      }),
                n
            )
        },
        S = () => {
            ;((u = new Date().getTime()),
                (e.autoplay.running = !0),
                x(),
                r(`autoplayStart`))
        },
        C = () => {
            ;((e.autoplay.running = !1),
                clearTimeout(a),
                cancelAnimationFrame(o),
                r(`autoplayStop`))
        },
        w = (t, n) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(clearTimeout(a), t || (h = !0))
            let i = () => {
                ;(r(`autoplayPause`),
                    e.params.autoplay.waitForTransition
                        ? e.wrapperEl.addEventListener(`transitionend`, _)
                        : T())
            }
            if (((e.autoplay.paused = !0), n)) {
                i()
                return
            }
            ;((l = (l || e.params.autoplay.delay) - (new Date().getTime() - u)),
                !(e.isEnd && l < 0 && !e.params.loop) &&
                    (l < 0 && (l = 0), i()))
        },
        T = () => {
            ;(e.isEnd && l < 0 && !e.params.loop) ||
                e.destroyed ||
                !e.autoplay.running ||
                ((u = new Date().getTime()),
                h ? ((h = !1), x(l)) : x(),
                (e.autoplay.paused = !1),
                r(`autoplayResume`))
        },
        E = () => {
            if (e.destroyed || !e.autoplay.running) return
            let t = vt()
            ;(t.visibilityState === `hidden` && ((h = !0), w(!0)),
                t.visibilityState === `visible` && T())
        },
        D = (t) => {
            t.pointerType === `mouse` &&
                ((h = !0),
                (g = !0),
                !(e.animating || e.autoplay.paused) && w(!0))
        },
        O = (t) => {
            t.pointerType === `mouse` && ((g = !1), e.autoplay.paused && T())
        },
        k = () => {
            e.params.autoplay.pauseOnMouseEnter &&
                (e.el.addEventListener(`pointerenter`, D),
                e.el.addEventListener(`pointerleave`, O))
        },
        A = () => {
            e.el &&
                typeof e.el != `string` &&
                (e.el.removeEventListener(`pointerenter`, D),
                e.el.removeEventListener(`pointerleave`, O))
        },
        j = () => {
            vt().addEventListener(`visibilitychange`, E)
        },
        M = () => {
            vt().removeEventListener(`visibilitychange`, E)
        }
    ;(n(`init`, () => {
        e.params.autoplay.enabled && (k(), j(), S())
    }),
        n(`destroy`, () => {
            ;(A(), M(), e.autoplay.running && C())
        }),
        n(`_freeModeStaticRelease`, () => {
            ;(p || h) && T()
        }),
        n(`_freeModeNoMomentumRelease`, () => {
            e.params.autoplay.disableOnInteraction ? C() : w(!0, !0)
        }),
        n(`beforeTransitionStart`, (t, n, r) => {
            e.destroyed ||
                !e.autoplay.running ||
                (r || !e.params.autoplay.disableOnInteraction ? w(!0, !0) : C())
        }),
        n(`sliderFirstMove`, () => {
            if (!(e.destroyed || !e.autoplay.running)) {
                if (e.params.autoplay.disableOnInteraction) {
                    C()
                    return
                }
                ;((f = !0),
                    (p = !1),
                    (h = !1),
                    (m = setTimeout(() => {
                        ;((h = !0), (p = !0), w(!0))
                    }, 200)))
            }
        }),
        n(`touchEnd`, () => {
            if (!(e.destroyed || !e.autoplay.running || !f)) {
                if (
                    (clearTimeout(m),
                    clearTimeout(a),
                    e.params.autoplay.disableOnInteraction)
                ) {
                    ;((p = !1), (f = !1))
                    return
                }
                ;(p && e.params.cssMode && T(), (p = !1), (f = !1))
            }
        }),
        n(`slideChange`, () => {
            e.destroyed ||
                !e.autoplay.running ||
                (e.autoplay.paused && ((l = b()), (s = b())))
        }),
        Object.assign(e.autoplay, { start: S, stop: C, pause: w, resume: T }))
}
function Or(e) {
    let {
        effect: t,
        swiper: n,
        on: r,
        setTranslate: i,
        setTransition: a,
        overwriteParams: o,
        perspective: s,
        recreateShadows: c,
        getEffectParams: l,
    } = e
    ;(r(`beforeInit`, () => {
        if (n.params.effect !== t) return
        ;(n.classNames.push(`${n.params.containerModifierClass}${t}`),
            s &&
                s() &&
                n.classNames.push(`${n.params.containerModifierClass}3d`))
        let e = o ? o() : {}
        ;(Object.assign(n.params, e), Object.assign(n.originalParams, e))
    }),
        r(`setTranslate _virtualUpdated`, () => {
            n.params.effect === t && i()
        }),
        r(`setTransition`, (e, r) => {
            n.params.effect === t && a(r)
        }),
        r(`transitionEnd`, () => {
            if (n.params.effect === t && c) {
                if (!l || !l().slideShadows) return
                ;(n.slides.forEach((e) => {
                    e.querySelectorAll(
                        `.swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left`,
                    ).forEach((e) => e.remove())
                }),
                    c())
            }
        }))
    let u
    r(`virtualUpdate`, () => {
        n.params.effect === t &&
            (n.slides.length || (u = !0),
            requestAnimationFrame(() => {
                u && n.slides && n.slides.length && (i(), (u = !1))
            }))
    })
}
function kr(e, t) {
    let n = jt(t)
    return (
        n !== t &&
            ((n.style.backfaceVisibility = `hidden`),
            (n.style[`-webkit-backface-visibility`] = `hidden`)),
        n
    )
}
function Ar({ swiper: e, duration: t, transformElements: n, allSlides: r }) {
    let { activeIndex: i } = e,
        a = (t) =>
            t.parentElement
                ? t.parentElement
                : e.slides.find(
                      (e) => e.shadowRoot && e.shadowRoot === t.parentNode,
                  )
    if (e.params.virtualTranslate && t !== 0) {
        let t = !1,
            o
        ;((o = r
            ? n
            : n.filter((t) => {
                  let n = t.classList.contains(`swiper-slide-transform`)
                      ? a(t)
                      : t
                  return e.getSlideIndex(n) === i
              })),
            o.forEach((n) => {
                Ht(n, () => {
                    if (t || !e || e.destroyed) return
                    ;((t = !0), (e.animating = !1))
                    let n = new window.CustomEvent(`transitionend`, {
                        bubbles: !0,
                        cancelable: !0,
                    })
                    e.wrapperEl.dispatchEvent(n)
                })
            }))
    }
}
function jr({ swiper: e, extendParams: t, on: n }) {
    ;(t({ fadeEffect: { crossFade: !1 } }),
        Or({
            effect: `fade`,
            swiper: e,
            on: n,
            setTranslate: () => {
                let { slides: t } = e,
                    n = e.params.fadeEffect
                for (let r = 0; r < t.length; r += 1) {
                    let t = e.slides[r],
                        i = -t.swiperSlideOffset
                    e.params.virtualTranslate || (i -= e.translate)
                    let a = 0
                    e.isHorizontal() || ((a = i), (i = 0))
                    let o = e.params.fadeEffect.crossFade
                            ? Math.max(1 - Math.abs(t.progress), 0)
                            : 1 + Math.min(Math.max(t.progress, -1), 0),
                        s = kr(n, t)
                    ;((s.style.opacity = o),
                        (s.style.transform = `translate3d(${i}px, ${a}px, 0px)`))
                }
            },
            setTransition: (t) => {
                let n = e.slides.map((e) => jt(e))
                ;(n.forEach((e) => {
                    e.style.transitionDuration = `${t}ms`
                }),
                    Ar({
                        swiper: e,
                        duration: t,
                        transformElements: n,
                        allSlides: !0,
                    }))
            },
            overwriteParams: () => ({
                slidesPerView: 1,
                slidesPerGroup: 1,
                watchSlidesProgress: !0,
                spaceBetween: 0,
                virtualTranslate: !e.params.cssMode,
            }),
        }))
}
var Mr = new WeakMap(),
    Nr = 300,
    Pr = 5e3
function Fr(e, t) {
    for (let n of t) {
        let t = e.getAttribute(n)
        if (t !== null && t !== ``) return t
    }
    return null
}
function Ir(e, t) {
    let n = Fr(e, t)
    if (n === null) return null
    let r = n.toLowerCase()
    return [`1`, `true`, `yes`, `on`].includes(r)
        ? !0
        : [`0`, `false`, `no`, `off`].includes(r)
          ? !1
          : null
}
function Lr(e, t) {
    let n = Fr(e, t)
    if (n === null) return null
    let r = Number.parseInt(n, 10)
    return Number.isNaN(r) ? null : r
}
function Rr(e) {
    let t = Fr(e, [`data-carousel-per-view`, `data-perview`])
    if (t === null) return 1
    if (t === `auto`) return `auto`
    let n = Number.parseInt(t, 10)
    return Number.isNaN(n) ? 1 : n
}
function zr(e) {
    let t = Fr(e, [`data-carousel-breakpoints`, `data-breakpoint`])
    if (t === null) return null
    try {
        return JSON.parse(t)
    } catch (e) {
        return (console.error(`Invalid JSON in carousel breakpoints:`, e), null)
    }
}
function Br(e) {
    let t = Fr(e, [`data-carousel-id`])
    if (t !== null) return t
    let n = `carousel-${Math.random().toString(36).slice(2, 10)}`
    return (e.setAttribute(`data-carousel-id`, n), n)
}
function Vr(e) {
    let t =
            Fr(e, [`data-carousel-effect`]) ??
            (Ir(e, [`data-fade`]) ? `fade` : `slide`),
        n = t === `fade` || Ir(e, [`data-carousel-fade`]) === !0,
        r = e.querySelectorAll(`.swiper-wrapper > .swiper-slide`).length,
        i = Ir(e, [`data-carousel-loop`, `data-loop`]) ?? !1,
        a = Ir(e, [`data-carousel-autoplay`, `data-auto`]) ?? !1,
        o = Ir(e, [`data-carousel-drag`, `data-drag`]),
        s = Ir(e, [`data-carousel-touch`, `data-carousel-swipe`]),
        c = Ir(e, [`data-carousel-wheel`, `data-wheel`]),
        l = Ir(e, [`data-carousel-pagination`]),
        u = Ir(e, [`data-carousel-navigation`]),
        d = n || t === `fade`,
        f = o ?? s ?? !d
    return {
        align: Fr(e, [`data-carousel-align`, `data-align`]) ?? `center`,
        autoplayDelay:
            Lr(e, [`data-carousel-autoplay-delay`, `data-delay`]) ?? Pr,
        autoplayDisableOnInteraction:
            Ir(e, [`data-carousel-disable-on-interaction`]) ?? !0,
        autoplayEnabled: a,
        breakpoints: zr(e),
        carouselId: Br(e),
        effect: d ? `fade` : t,
        fadeEnabled: d,
        grabCursor: f,
        initialSlide: Lr(e, [`data-carousel-initial-slide`]) ?? 0,
        interactionEnabled: f,
        loop: i,
        navigationEnabled: u,
        paginationEnabled: l,
        pauseOnMouseEnter: Ir(e, [`data-carousel-pause-on-hover`]) ?? !0,
        perView: Rr(e),
        rewind: Ir(e, [`data-carousel-rewind`]) ?? !1,
        slideCount: r,
        speed: Lr(e, [`data-carousel-speed`]) ?? Nr,
        watchOverflow: Ir(e, [`data-carousel-watch-overflow`]) ?? !0,
        wheelEnabled: c ?? !1,
    }
}
function Hr(e, t = Vr(e)) {
    let n = `[data-carousel-controls="${t.carouselId}"]`,
        r =
            e.querySelector(`.swiper-controls`) ??
            e.parentElement?.querySelector(n) ??
            e.parentElement?.querySelector(`.swiper-controls`) ??
            e.closest(`[data-carousel-scope]`)?.querySelector(n) ??
            e.ownerDocument.querySelector(n)
    return {
        controls: r,
        dotsNode: r?.querySelector(`.swiper-pagination`) ?? null,
        nextBtn: r?.querySelector(`.swiper-button-next`) ?? null,
        prevBtn: r?.querySelector(`.swiper-button-prev`) ?? null,
    }
}
function Ur() {
    return function (e, t) {
        return `<button type="button" class="${t}" data-carousel-bullet-index="${e}" aria-label="Go to slide ${e + 1}"></button>`
    }
}
function Wr(e, t) {
    let n = t.realIndex ?? t.activeIndex
    e.querySelectorAll(
        `.swiper-wrapper > .swiper-slide:not(.swiper-slide-duplicate)`,
    ).forEach((e, t) => {
        e.classList.toggle(`swiper-slide-selected`, t === n)
    })
}
function Gr(e, t, n, r) {
    t.dotsNode &&
        t.dotsNode
            .querySelectorAll(`.swiper-pagination-bullet`)
            .forEach((t, i) => {
                t.dataset.carouselPaginationBound !== n.carouselId &&
                    ((t.dataset.carouselPaginationBound = n.carouselId),
                    t.addEventListener(
                        `click`,
                        (r) => {
                            if ((r.preventDefault(), e.destroyed)) return
                            let a = Number.parseInt(
                                    t.dataset.carouselBulletIndex ?? ``,
                                    10,
                                ),
                                o = Number.isNaN(a) ? i : a
                            if (
                                n.loop &&
                                typeof e.getSlideIndexByData == `function`
                            ) {
                                e.slideTo(e.getSlideIndexByData(o))
                                return
                            }
                            e.slideTo(o)
                        },
                        { signal: r },
                    ))
            })
}
function Kr(e, t, n) {
    if ((e.classList.toggle(`swiper-disabled`, !n), t.autoplay)) {
        if (n) {
            t.autoplay.start()
            return
        }
        t.autoplay.stop()
    }
}
function CapellKeepAutoplay(e, t, n) {
    !n.autoplayEnabled ||
        t.destroyed ||
        e.classList.contains(`swiper-disabled`) ||
        !t.autoplay ||
        window.setTimeout(() => {
            !t.destroyed &&
                !e.classList.contains(`swiper-disabled`) &&
                t.autoplay.start()
        }, 0)
}
function qr(e, t) {
    let n = new IntersectionObserver((n) => {
        n.forEach((n) => {
            Kr(e, t, n.isIntersecting)
        })
    })
    return (n.observe(e), n)
}
function Jr(e, t, n) {
    e.querySelectorAll(`img`).forEach((r) => {
        r.addEventListener(
            `load`,
            () => {
                ;(t.update(), Kr(e, t, !0))
            },
            { signal: n },
        )
    })
}
function Yr(e, t, n, r) {
    let i = [],
        a = {
            allowTouchMove: t.interactionEnabled,
            centeredSlides: t.align === `center`,
            grabCursor: t.grabCursor,
            initialSlide: t.initialSlide,
            loop: t.loop,
            observeParents: !0,
            observer: !0,
            preventClicks: !1,
            preventClicksPropagation: !1,
            rewind: t.loop ? !1 : t.rewind,
            slidesPerView: t.fadeEnabled ? 1 : t.perView,
            spaceBetween: 0,
            speed: t.speed,
            watchOverflow: t.watchOverflow,
            watchSlidesProgress: !0,
        }
    return (
        t.breakpoints && (a.breakpoints = t.breakpoints),
        (t.navigationEnabled ?? !!(n.prevBtn || n.nextBtn)) &&
            (n.prevBtn || n.nextBtn) &&
            (i.push(wr),
            (a.navigation = {
                disabledClass: `swiper-button-disabled`,
                nextEl: n.nextBtn,
                prevEl: n.prevBtn,
            })),
        (t.paginationEnabled ?? !!n.dotsNode) &&
            n.dotsNode &&
            (i.push(Er),
            (a.pagination = {
                bulletActiveClass: `swiper-pagination-bullet-active`,
                bulletClass: `swiper-pagination-bullet`,
                clickable: !1,
                el: n.dotsNode,
                renderBullet: Ur(),
            })),
        t.autoplayEnabled &&
            (i.push(Dr),
            (a.autoplay = {
                delay: t.autoplayDelay,
                disableOnInteraction: t.autoplayDisableOnInteraction,
                pauseOnMouseEnter: t.pauseOnMouseEnter,
                stopOnLastSlide: !1,
            })),
        t.fadeEnabled &&
            (i.push(jr),
            (a.centeredSlides = !0),
            (a.effect = `fade`),
            (a.fadeEffect = { crossFade: !0 })),
        t.wheelEnabled &&
            (i.push(xr),
            (a.mousewheel = { forceToAxis: !0, releaseOnEdges: !0 })),
        (a.modules = i),
        (a.on = {
            init() {
                ;(e.classList.add(`swiper-ready`),
                    Wr(e, this),
                    Gr(this, n, t, r),
                    CapellKeepAutoplay(e, this, t))
            },
            autoplayStop() {
                CapellKeepAutoplay(e, this, t)
            },
            paginationRender() {
                Gr(this, n, t, r)
            },
            paginationUpdate() {
                Gr(this, n, t, r)
            },
            resize() {
                this.update()
            },
            slideChange() {
                ;(Wr(e, this),
                    Gr(this, n, t, r),
                    CapellKeepAutoplay(e, this, t))
            },
            slideChangeTransitionEnd() {
                CapellKeepAutoplay(e, this, t)
            },
        }),
        a
    )
}
function Xr(e) {
    let t = Mr.get(e)
    t &&
        (t.abortController.abort(),
        t.observer?.disconnect(),
        t.swiper && !t.swiper.destroyed && t.swiper.destroy(!0, !0),
        delete e.swiper,
        delete e.dataset.initialized,
        e.classList.remove(`swiper-disabled`, `swiper-ready`),
        Mr.delete(e))
}
function Zr(e) {
    if (!(e instanceof HTMLElement) || !e.querySelector(`.swiper-wrapper`))
        return null
    Xr(e)
    let t = Vr(e),
        n = Hr(e, t),
        r = new AbortController(),
        i = new br(e, Yr(e, t, n, r.signal))
    ;(Jr(e, i, r.signal),
        e.addEventListener(`enable-carousel`, () => Kr(e, i, !0), {
            signal: r.signal,
        }),
        e.addEventListener(`disable-carousel`, () => Kr(e, i, !1), {
            signal: r.signal,
        }))
    let a = qr(e, i)
    return (
        Mr.set(e, { abortController: r, observer: a, options: t, swiper: i }),
        (e.dataset.initialized = `true`),
        (e.swiper = i),
        i
    )
}
function Qr(e = document) {
    e.querySelectorAll(`.swiper`).forEach((e) => {
        e.dataset.initialized || Zr(e)
    })
}
;(typeof document < `u` &&
    (Qr(),
    document.addEventListener(`livewire:navigated`, () => {
        Qr()
    })),
    document.addEventListener(`alpine:init`, () => {
        ;(window.Alpine.plugin(mt), window.Alpine.plugin(Ze))
    }))
;(() => {
    const setSpotlightPanel = (spotlight, activeIndex) => {
        spotlight.querySelectorAll(`[data-spotlight-tab]`).forEach((tab) => {
            const isActive =
                Number(tab.dataset.spotlightIndex ?? 0) === activeIndex

            tab.dataset.active = isActive ? `true` : `false`
            tab.setAttribute(`aria-selected`, isActive ? `true` : `false`)
            tab.tabIndex = isActive ? 0 : -1
        })

        spotlight
            .querySelectorAll(`[data-spotlight-panel]`)
            .forEach((panel) => {
                panel.hidden =
                    Number(panel.dataset.spotlightIndex ?? 0) !== activeIndex
            })
    }

    const activateSpotlightTab = (tab) => {
        const spotlight = tab.closest(`[data-theme-spotlight]`)

        if (!spotlight) {
            return
        }

        setSpotlightPanel(spotlight, Number(tab.dataset.spotlightIndex ?? 0))
    }

    const moveSpotlightTabFocus = (tab, direction) => {
        const spotlight = tab.closest(`[data-theme-spotlight]`)
        const tabs = Array.from(
            spotlight?.querySelectorAll(`[data-spotlight-tab]`) ?? [],
        )

        if (tabs.length === 0) {
            return
        }

        const currentIndex = tabs.indexOf(tab)
        const nextTab =
            tabs[(currentIndex + direction + tabs.length) % tabs.length]

        nextTab.focus()
        activateSpotlightTab(nextTab)
    }

    const initSpotlights = (root = document) => {
        root.querySelectorAll(`[data-theme-spotlight]`).forEach((spotlight) => {
            if (spotlight.dataset.initialized === `true`) {
                return
            }

            const activeTab =
                spotlight.querySelector(
                    `[data-spotlight-tab][aria-selected="true"]`,
                ) ?? spotlight.querySelector(`[data-spotlight-tab]`)

            if (activeTab) {
                setSpotlightPanel(
                    spotlight,
                    Number(activeTab.dataset.spotlightIndex ?? 0),
                )
            }

            spotlight.dataset.initialized = `true`
        })
    }

    if (typeof document === `undefined`) {
        return
    }

    document.addEventListener(`click`, (event) => {
        const spotlightTab = event.target.closest(`[data-spotlight-tab]`)

        if (spotlightTab) {
            activateSpotlightTab(spotlightTab)
        }
    })

    document.addEventListener(`keydown`, (event) => {
        const spotlightTab = event.target.closest(`[data-spotlight-tab]`)

        if (
            !spotlightTab ||
            ![`ArrowDown`, `ArrowRight`, `ArrowUp`, `ArrowLeft`].includes(
                event.key,
            )
        ) {
            return
        }

        event.preventDefault()
        moveSpotlightTabFocus(
            spotlightTab,
            [`ArrowDown`, `ArrowRight`].includes(event.key) ? 1 : -1,
        )
    })

    if (document.readyState === `loading`) {
        document.addEventListener(`DOMContentLoaded`, () => initSpotlights())
    } else {
        initSpotlights()
    }

    document.addEventListener(`livewire:navigated`, () => initSpotlights())
})()
;(() => {
    const setPathwayPanel = (pathways, activePanel) => {
        pathways.querySelectorAll(`[data-pathway-panel]`).forEach((panel) => {
            const isActive = panel === activePanel

            if (!isActive && panel.open) {
                panel.open = false
            }

            panel.dataset.active = isActive && panel.open ? `true` : `false`
        })
    }

    const initPathways = (root = document) => {
        root.querySelectorAll(`[data-theme-pathways]`).forEach((pathways) => {
            if (pathways.dataset.initialized === `true`) {
                return
            }

            const panels = Array.from(
                pathways.querySelectorAll(`[data-pathway-panel]`),
            )
            const openPanel = panels.find((panel) => panel.open) ?? panels[0]

            if (openPanel) {
                openPanel.open = true
                setPathwayPanel(pathways, openPanel)
            }

            panels.forEach((panel) => {
                panel.addEventListener(`toggle`, () => {
                    if (panel.open) {
                        setPathwayPanel(pathways, panel)
                    } else {
                        panel.dataset.active = `false`
                    }
                })
            })

            pathways.dataset.initialized = `true`
        })
    }

    if (typeof document === `undefined`) {
        return
    }

    if (document.readyState === `loading`) {
        document.addEventListener(`DOMContentLoaded`, () => initPathways())
    } else {
        initPathways()
    }

    document.addEventListener(`livewire:navigated`, () => initPathways())
})()
