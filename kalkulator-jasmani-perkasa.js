/* ============================================================
   BINJAS PERKASA v2 -- Kalkulator Samapta
   ============================================================ */
'use strict';

/* Global: menyimpan hasil kalkulasi terakhir untuk PDF */
let lastResult = null;

/* Cache PDF yang sudah di-generate supaya share/download terjadi
 * SECARA LANGSUNG dari user click -- penting untuk HP karena
 * Web Share API & anchor click butuh user-activation yang fresh.
 * Kalau kita generate PDF baru after-await, user-activation sudah
 * expire di banyak browser HP -> share/download diblokir. */
let cachedPdf = null;            // { blob, file, filename, blobUrl, signature }
let pdfBuilding = false;         // mencegah build paralel

/* Logo SVG sebagai inline string untuk dipakai di PDF (html2canvas
 * lebih reliabel dengan inline SVG dibanding <img src="...svg">) */
let LOGO_SVG_INLINE = null; // No longer used in v5 PDF (pure jsPDF)
function preloadLogoSVG() {
  fetch('img/logo.svg')
    .then(r => r.ok ? r.text() : Promise.reject())
    .then(svg => {
      // Bersihkan XML declaration untuk inlining yang aman
      LOGO_SVG_INLINE = svg.replace(/<\?xml[^?]*\?>/g, '').trim();
    })
    .catch(() => { LOGO_SVG_INLINE = null; });
}

/* --- DATA TABLES (Tabel Penilaian Resmi 2026) --------------- */
const ST = {
  polri:{
    pria:{
      lari:[[3444,100],[3422,99],[3401,98],[3380,97],[3359,96],[3338,95],[3317,94],[3296,93],[3275,92],[3253,91],[3232,90],[3211,89],[3190,88],[3169,87],[3148,86],[3127,85],[3105,84],[3084,83],[3062,82],[3041,81],[3021,80],[2999,79],[2978,78],[2957,77],[2936,76],[2914,75],[2893,74],[2872,73],[2851,72],[2830,71],[2809,70],[2788,69],[2767,68],[2746,67],[2725,66],[2703,65],[2682,64],[2661,63],[2639,62],[2618,61],[2597,60],[2576,59],[2555,58],[2534,57],[2513,56],[2491,55],[2470,54],[2449,53],[2428,52],[2407,51],[2386,50],[2364,49],[2343,48],[2322,47],[2301,46],[2280,45],[2259,44],[2237,43],[2216,42],[2195,41],[2174,40],[2153,39],[2132,38],[2111,37],[2090,36],[2069,35],[2048,34],[2026,33],[2005,32],[1984,31],[1962,30],[1941,29],[1920,28],[1899,27],[1878,26],[1857,25],[1836,24],[1814,23],[1793,22],[1772,21],[1750,20],[1729,19],[1708,18],[1687,17],[1666,16],[1645,15],[1625,14],[1603,13],[1582,12],[1561,11],[1539,10],[1518,9],[1497,8],[1476,7],[1455,6],[1434,5],[1413,4],[1392,3],[1371,2],[1349,1]],
      pullup:[[17,100],[16,94],[15,88],[14,82],[13,76],[12,70],[11,64],[10,58],[9,52],[8,46],[7,40],[6,32],[5,26],[4,20],[3,14],[2,8],[1,4]],
      situp:[[40,100],[39,96],[38,92],[37,88],[36,84],[35,80],[34,76],[33,72],[32,68],[31,64],[30,60],[29,56],[28,52],[27,48],[26,44],[25,41],[24,38],[23,35],[22,32],[21,30],[20,28],[19,26],[18,24],[17,22],[16,20],[15,18],[14,16],[13,14],[12,12],[11,10],[10,8],[9,6],[8,4],[7,2],[6,1]],
      pushup:[[42,100],[41,97],[40,94],[39,91],[38,88],[37,85],[36,82],[35,79],[34,76],[33,73],[32,70],[31,67],[30,64],[29,61],[28,58],[27,55],[26,52],[25,50],[24,48],[23,46],[22,44],[21,42],[20,40],[19,38],[18,36],[17,34],[16,32],[15,29],[14,26],[13,23],[12,21],[11,19],[10,17],[9,15],[8,13],[7,11],[6,9],[5,7],[4,5],[3,3],[2,2],[1,1]],
      shuttle:[[16.2,100],[16.3,99],[16.4,98],[16.5,97],[16.6,96],[16.7,95],[16.8,94],[16.9,92],[17.0,90],[17.1,88],[17.2,86],[17.3,84],[17.4,82],[17.5,80],[17.6,78],[17.7,76],[17.8,74],[17.9,72],[18.0,70],[18.1,68],[18.2,66],[18.3,64],[18.4,62],[18.5,60],[18.6,58],[18.7,56],[18.8,54],[18.9,52],[19.0,51],[19.1,49],[19.2,47],[19.3,45],[19.4,43],[19.5,41],true],
      lunges:[[54,100],[53,95],[52,90],[51,85],[50,80],[49,75],[48,70],[47,65],[46,60],[45,55],[44,50],[43,45],[42,41],[41,37],[40,33],[39,29],[38,25],[37,21],[36,17],[35,13],[34,9],[33,5],[32,1]],
      renang:[[14.0,100],[14.7,99],[15.4,98],[16.1,97],[16.8,96],[17.5,95],[18.2,94],[18.9,93],[19.6,92],[20.3,91],[21.0,90],[21.7,89],[22.4,88],[23.1,87],[23.8,86],[24.5,85],[25.2,84],[25.9,83],[26.6,82],[27.3,81],[28.0,80],[28.7,79],[29.4,78],[30.1,77],[30.8,76],[31.5,75],[32.2,74],[32.9,73],[33.6,72],[34.3,71],[35.0,70],[35.7,69],[36.4,68],[37.1,67],[37.8,66],[38.5,65],[39.2,64],[39.9,63],[40.6,62],[41.3,61],[42.0,60],[42.7,59],[43.4,58],[44.1,57],[44.8,56],[45.5,55],[46.2,54],[46.9,53],[47.6,52],[48.3,51],[49.0,50],[49.7,49],[50.4,48],[51.1,47],[51.8,46],[52.5,45],[53.2,44],[53.9,43],[54.6,42],[55.0,41],true]
    },
    wanita:{
      chinning:[[72,100],[71,97],[70,95],[69,92],[68,90],[67,87],[66,85],[65,82],[64,80],[63,77],[62,75],[61,72],[60,70],[59,67],[58,65],[57,62],[56,60],[55,57],[54,55],[53,52],[52,50],[51,47],[50,45],[49,42],[48,40],[47,37],[46,35],[45,32],[44,30],[43,27],[42,25],[41,22],[40,20],[39,17],[38,15],[37,12],[36,10],[35,7],[34,5],[33,2],[32,1]],
      situp:[[50,100],[49,96],[48,93],[47,91],[46,87],[45,84],[44,82],[43,78],[42,75],[41,73],[40,69],[39,66],[38,64],[37,60],[36,57],[35,55],[34,51],[33,48],[32,46],[31,42],[30,39],[29,37],[28,33],[27,29],[26,26],[25,24],[24,21],[23,19],[22,15],[21,12],[20,10],[19,6],[18,3],[17,1]],
      pushup:[[37,100],[36,97],[35,93],[34,90],[33,86],[32,83],[31,79],[30,76],[29,72],[28,69],[27,65],[26,62],[25,58],[24,55],[23,51],[22,48],[21,44],[20,41],[19,37],[18,34],[17,30],[16,27],[15,23],[14,20],[13,16],[12,13],[11,9],[10,6],[9,2],[8,1]],
      lari:[[3095,100],[3084,99],[3062,98],[3041,97],[3020,96],[2999,95],[2978,94],[2957,93],[2936,92],[2914,91],[2893,90],[2872,89],[2851,88],[2830,87],[2809,86],[2788,85],[2767,84],[2746,83],[2725,82],[2703,81],[2682,80],[2661,79],[2639,78],[2618,77],[2597,76],[2576,75],[2555,74],[2534,73],[2513,72],[2491,71],[2470,70],[2449,69],[2428,68],[2407,67],[2385,66],[2364,65],[2343,64],[2322,63],[2301,62],[2280,61],[2259,60],[2237,59],[2216,58],[2195,57],[2174,56],[2153,55],[2132,54],[2111,53],[2090,52],[2069,51],[2048,50],[2026,49],[2005,48],[1984,47],[1962,46],[1941,45],[1920,44],[1899,43],[1878,42],[1857,41]],
      shuttle:[[17.6,100],[17.7,99],[17.8,98],[17.9,97],[18.0,96],[18.1,95],[18.2,94],[18.3,93],[18.4,92],[18.5,91],[18.6,90],[18.7,89],[18.8,88],[18.9,87],[19.0,86],[19.1,85],[19.2,84],[19.3,83],[19.4,82],[19.5,81],[19.6,80],[19.7,79],[19.8,78],[19.9,77],[20.0,76],[20.1,75],[20.2,74],[20.3,73],[20.4,72],[20.5,71],[20.6,70],[20.7,69],[20.8,68],[20.9,67],[21.0,66],[21.1,65],[21.2,64],[21.3,63],[21.4,62],[21.5,61],[21.6,60],[21.7,59],[21.8,58],[21.9,57],[22.0,56],[22.1,55],[22.2,54],[22.3,53],[22.4,52],[22.5,51],[22.6,50],[22.7,49],[22.8,48],[22.9,47],[23.0,46],[23.1,45],[23.2,44],[23.3,43],[23.4,42],[23.5,41],true],
      lunges:[[45,100],[44,95],[43,90],[42,85],[41,80],[40,75],[39,70],[38,65],[37,60],[36,55],[35,50],[34,45],[33,41],[32,37],[31,33],[30,29],[29,25],[28,21],[27,17],[26,13],[25,9],[24,5],[23,1]],
      renang:[[20.0,100],[20.7,99],[21.3,98],[22.0,97],[22.7,96],[23.4,95],[24.0,94],[24.7,93],[25.4,92],[26.0,91],[26.7,90],[27.4,89],[28.0,88],[28.7,87],[29.4,86],[30.1,85],[30.7,84],[31.4,83],[32.1,82],[32.7,81],[33.4,80],[34.1,79],[34.7,78],[35.4,77],[36.1,76],[36.8,75],[37.4,74],[38.1,73],[38.8,72],[39.4,71],[40.1,70],[40.8,69],[41.4,68],[42.1,67],[42.8,66],[43.5,65],[44.1,64],[44.8,63],[45.5,62],[46.1,61],[46.8,60],[47.5,59],[48.1,58],[48.8,57],[49.5,56],[50.2,55],[50.8,54],[51.5,53],[52.2,52],[52.8,51],[53.5,50],[54.2,49],[54.8,48],[55.5,47],[56.2,46],[56.9,45],[57.5,44],[58.2,43],[58.9,42],[60.0,41],true]
    }
  },
  tni:{
    pria:{
      lari:[[3507,100],[3488,99],[3469,98],[3450,97],[3431,96],[3412,95],[3393,94],[3374,93],[3355,92],[3336,91],[3317,90],[3298,89],[3279,88],[3260,87],[3241,86],[3222,85],[3203,84],[3184,83],[3165,82],[3146,81],[3127,80],[3108,79],[3089,78],[3070,77],[3051,76],[3032,75],[3013,74],[2994,73],[2975,72],[2956,71],[2937,70],[2918,69],[2899,68],[2880,67],[2861,66],[2842,65],[2823,64],[2804,63],[2785,62],[2766,61],[2747,60],[2728,59],[2709,58],[2690,57],[2671,56],[2652,55],[2633,54],[2614,53],[2595,52],[2576,51],[2557,50],[2538,49],[2519,48],[2500,47],[2481,46],[2462,45],[2443,44],[2424,43],[2405,42],[2386,41],[2367,40],[2348,39],[2329,38],[2310,37],[2291,36],[2272,35],[2253,34],[2234,33],[2215,32],[2196,31],[2177,30],[2158,29],[2139,28],[2120,27],[2101,26],[2082,25],[2063,24],[2044,23],[2025,22],[2006,21],[1987,20],[1968,19],[1949,18],[1930,17],[1911,16],[1892,15],[1873,14],[1854,13],[1835,12],[1816,11],[1797,10],[1778,9],[1759,8],[1740,7],[1721,6],[1702,5],[1683,4],[1664,3],[1645,2],[1626,1]],
      pullup:[[18,100],[17,95],[16,90],[15,85],[14,80],[13,75],[12,70],[11,65],[10,60],[9,55],[8,50],[7,45],[6,40],[5,35],[4,30],[3,25],[2,20],[1,15]],
      situp:[[41,100],[40,96],[39,92],[38,88],[37,83],[36,79],[35,75],[34,71],[33,67],[32,63],[31,58],[30,54],[29,50],[28,46],[27,42],[26,38],[25,33],[24,29],[23,25],[22,21],[21,17],[20,13],[19,9],[18,5],[17,1]],
      pushup:[[43,100],[42,97],[41,94],[40,91],[39,88],[38,85],[37,82],[36,78],[35,75],[34,72],[33,69],[32,66],[31,63],[30,60],[29,56],[28,53],[27,50],[26,47],[25,44],[24,41],[23,38],[22,34],[21,31],[20,28],[19,25],[18,22],[17,19],[16,15]],
      shuttle:[[15.90,100],[16.00,99],[16.10,98],[16.20,97],[16.30,96],[16.40,95],[16.50,94],[16.60,93],[16.70,92],[16.80,91],[16.90,90],[17.00,89],[17.10,88],[17.20,87],[17.30,86],[17.40,85],[17.50,84],[17.60,83],[17.70,82],[17.80,81],[17.90,80],[18.00,79],[18.10,78],[18.20,77],[18.30,76],[18.40,75],[18.50,74],[18.60,73],[18.70,72],[18.80,71],[18.90,70],[19.00,69],[19.10,68],[19.20,67],[19.30,66],[19.40,65],[19.50,64],[19.60,63],[19.70,62],[19.80,61],[19.90,60],[20.00,59],[20.10,58],[20.20,57],[20.30,56],[20.40,55],[20.50,54],[20.60,53],[20.70,52],[20.80,51],[20.90,50],[21.00,49],[21.10,48],[21.20,47],[21.30,46],[21.40,45],[21.50,44],[21.60,43],[21.70,42],[21.80,41],[21.90,40],[22.00,39],[22.10,38],[22.20,37],[22.30,36],[22.40,35],[22.50,34],[22.60,33],[22.70,32],[22.80,31],[22.90,30],[23.00,29],[23.10,28],[23.20,27],[23.30,26],[23.40,25],[23.50,24],[23.60,23],[23.70,22],[23.80,21],[23.90,20],[24.00,19],[24.10,18],[24.20,17],[24.30,16],[24.40,15],[24.50,14],[24.60,13],[24.70,12],[24.80,11],[24.90,10],[25.00,9],[25.10,8],[25.20,7],[25.30,6],[25.40,5],[25.50,4],[25.60,3],[25.70,2],[25.80,1],true],
      lunges:[[54,100],[53,95],[52,90],[51,85],[50,80],[49,75],[48,70],[47,65],[46,60],[45,55],[44,50],[43,45],[42,41],[41,37],[40,33],[39,29],[38,25],[37,21],[36,17],[35,13],[34,9],[33,5],[32,1]],
      renang:{
        dada:[[40,100],[41,99],[42,98],[43,97],[44,96],[45,95],[46,94],[47,93],[48,92],[49,91],[50,90],[51,89],[52,88],[53,87],[54,86],[55,85],[56,84],[57,83],[58,82],[59,81],[60,80],[61,79],[62,78],[63,77],[64,76],[65,75],[66,74],[67,73],[68,72],[69,71],[70,70],[71,69],[72,68],[73,67],[74,66],[75,65],[76,64],[77,63],[78,62],[79,61],[80,60],[81,59],[82,58],[83,57],[84,56],[85,55],[86,54],[87,53],[88,52],[89,51],[90,50],[91,49],[92,48],[93,47],[94,46],[95,45],[96,44],[97,43],[98,42],[99,41],[100,40],[101,39],[102,38],[103,37],[104,36],[105,35],[106,34],[107,33],[108,32],[109,31],[110,30],[111,29],[112,28],[113,27],[114,26],[115,25],[116,24],[117,23],[118,22],[119,21],[120,20],[121,19],[122,18],[123,17],[124,16],[125,15],[126,14],[127,13],[128,12],[129,11],[130,10],[131,9],[132,8],[133,7],[134,6],[135,5],[136,4],[137,3],[138,2],[139,1],true],
        bebas:[[34,100],[35,99],[36,98],[37,97],[38,96],[39,95],[40,94],[41,93],[42,92],[43,91],[44,90],[45,89],[46,88],[47,87],[48,86],[49,85],[50,84],[51,83],[52,82],[53,81],[54,80],[55,79],[56,78],[57,77],[58,76],[59,75],[60,74],[61,73],[62,72],[63,71],[64,70],[65,69],[66,68],[67,67],[68,66],[69,65],[70,64],[71,63],[72,62],[73,61],[74,60],[75,59],[76,58],[77,57],[78,56],[79,55],[80,54],[81,53],[82,52],[83,51],[84,50],[85,49],[86,48],[87,47],[88,46],[89,45],[90,44],[91,43],[92,42],[93,41],[94,40],[95,39],[96,38],[97,37],[98,36],[99,35],[100,34],[101,33],[102,32],[103,31],[104,30],[105,29],[106,28],[107,27],[108,26],[109,25],[110,24],[111,23],[112,22],[113,21],[114,20],[115,19],[116,18],[117,17],[118,16],[119,15],[120,14],[121,13],[122,12],[123,11],[124,10],[125,9],[126,8],[127,7],[128,6],[129,5],[130,4],[131,3],[132,2],[133,1],true]
      }
    },
    wanita:{
      chinning:[[72,100],[71,97],[70,95],[69,92],[68,90],[67,87],[66,85],[65,82],[64,80],[63,77],[62,75],[61,72],[60,70],[59,67],[58,65],[57,62],[56,60],[55,57],[54,55],[53,52],[52,50],[51,47],[50,45],[49,42],[48,40],[47,37],[46,35],[45,32],[44,30],[43,27],[42,25],[41,22],[40,20],[39,17],[38,15],[37,12],[36,10],[35,7],[34,5],[33,2],[32,1]],
      situp:[[50,100],[49,96],[48,93],[47,91],[46,87],[45,84],[44,82],[43,78],[42,75],[41,73],[40,69],[39,66],[38,64],[37,60],[36,57],[35,55],[34,51],[33,48],[32,46],[31,42],[30,39],[29,37],[28,33],[27,29],[26,26],[25,24],[24,21],[23,19],[22,15],[21,12],[20,10],[19,6],[18,3],[17,1]],
      pushup:[[37,100],[36,97],[35,93],[34,90],[33,86],[32,83],[31,79],[30,76],[29,72],[28,69],[27,65],[26,62],[25,58],[24,55],[23,51],[22,48],[21,44],[20,41],[19,37],[18,34],[17,30],[16,27],[15,23],[14,20],[13,16],[12,13],[11,9],[10,6],[9,2],[8,1]],
      lari:[[3095,100],[3084,99],[3062,98],[3041,97],[3020,96],[2999,95],[2978,94],[2957,93],[2936,92],[2914,91],[2893,90],[2872,89],[2851,88],[2830,87],[2809,86],[2788,85],[2767,84],[2746,83],[2725,82],[2703,81],[2682,80],[2661,79],[2639,78],[2618,77],[2597,76],[2576,75],[2555,74],[2534,73],[2513,72],[2491,71],[2470,70],[2449,69],[2428,68],[2407,67],[2385,66],[2364,65],[2343,64],[2322,63],[2301,62],[2280,61],[2259,60],[2237,59],[2216,58],[2195,57],[2174,56],[2153,55],[2132,54],[2111,53],[2090,52],[2069,51],[2048,50],[2026,49],[2005,48],[1984,47],[1962,46],[1941,45],[1920,44],[1899,43],[1878,42],[1857,41]],
      shuttle:[[17.6,100],[17.7,99],[17.8,98],[17.9,97],[18.0,96],[18.1,95],[18.2,94],[18.3,93],[18.4,92],[18.5,91],[18.6,90],[18.7,89],[18.8,88],[18.9,87],[19.0,86],[19.1,85],[19.2,84],[19.3,83],[19.4,82],[19.5,81],[19.6,80],[19.7,79],[19.8,78],[19.9,77],[20.0,76],[20.1,75],[20.2,74],[20.3,73],[20.4,72],[20.5,71],[20.6,70],[20.7,69],[20.8,68],[20.9,67],[21.0,66],[21.1,65],[21.2,64],[21.3,63],[21.4,62],[21.5,61],[21.6,60],[21.7,59],[21.8,58],[21.9,57],[22.0,56],[22.1,55],[22.2,54],[22.3,53],[22.4,52],[22.5,51],[22.6,50],[22.7,49],[22.8,48],[22.9,47],[23.0,46],[23.1,45],[23.2,44],[23.3,43],[23.4,42],[23.5,41],true],
      lunges:[[45,100],[44,95],[43,90],[42,85],[41,80],[40,75],[39,70],[38,65],[37,60],[36,55],[35,50],[34,45],[33,41],[32,37],[31,33],[30,29],[29,25],[28,21],[27,17],[26,13],[25,9],[24,5],[23,1]],
      renang:{
        dada:[[40,100],[41,99],[42,98],[43,97],[44,96],[45,95],[46,94],[47,93],[48,92],[49,91],[50,90],[51,89],[52,88],[53,87],[54,86],[55,85],[56,84],[57,83],[58,82],[59,81],[60,80],[61,79],[62,78],[63,77],[64,76],[65,75],[66,74],[67,73],[68,72],[69,71],[70,70],[71,69],[72,68],[73,67],[74,66],[75,65],[76,64],[77,63],[78,62],[79,61],[80,60],[81,59],[82,58],[83,57],[84,56],[85,55],[86,54],[87,53],[88,52],[89,51],[90,50],[91,49],[92,48],[93,47],[94,46],[95,45],[96,44],[97,43],[98,42],[99,41],[100,40],[101,39],[102,38],[103,37],[104,36],[105,35],[106,34],[107,33],[108,32],[109,31],[110,30],[111,29],[112,28],[113,27],[114,26],[115,25],[116,24],[117,23],[118,22],[119,21],[120,20],[121,19],[122,18],[123,17],[124,16],[125,15],[126,14],[127,13],[128,12],[129,11],[130,10],[131,9],[132,8],[133,7],[134,6],[135,5],[136,4],[137,3],[138,2],[139,1],true],
        bebas:[[34,100],[35,99],[36,98],[37,97],[38,96],[39,95],[40,94],[41,93],[42,92],[43,91],[44,90],[45,89],[46,88],[47,87],[48,86],[49,85],[50,84],[51,83],[52,82],[53,81],[54,80],[55,79],[56,78],[57,77],[58,76],[59,75],[60,74],[61,73],[62,72],[63,71],[64,70],[65,69],[66,68],[67,67],[68,66],[69,65],[70,64],[71,63],[72,62],[73,61],[74,60],[75,59],[76,58],[77,57],[78,56],[79,55],[80,54],[81,53],[82,52],[83,51],[84,50],[85,49],[86,48],[87,47],[88,46],[89,45],[90,44],[91,43],[92,42],[93,41],[94,40],[95,39],[96,38],[97,37],[98,36],[99,35],[100,34],[101,33],[102,32],[103,31],[104,30],[105,29],[106,28],[107,27],[108,26],[109,25],[110,24],[111,23],[112,22],[113,21],[114,20],[115,19],[116,18],[117,17],[118,16],[119,15],[120,14],[121,13],[122,12],[123,11],[124,10],[125,9],[126,8],[127,7],[128,6],[129,5],[130,4],[131,3],[132,2],[133,1],true]
      }
    }
  }
};

/* --- CORE HELPERS ------------------------------------------ */

function score(val, table) {
  if (!table || val <= 0) return 0;
  const isTime = table[table.length - 1] === true;
  for (const e of table) {
    if (typeof e === 'boolean') continue;
    const [t, s] = e;
    if (isTime ? val <= t : val >= t) return s;
  }
  return 1; // below minimum range -> still gets 1
}

function rating(s) {
  if (s >= 81) return { label:'Baik Sekali', cls:'bs' };
  if (s >= 71) return { label:'Baik',        cls:'b'  };
  if (s >= 61) return { label:'Cukup',       cls:'c'  };
  if (s >= 41) return { label:'Kurang',      cls:'k'  };
  return           { label:'Kurang Sekali', cls:'ks' };
}

function tierClass(s) {
  if (s <= 0)  return 'tier-z';
  if (s >= 81) return 'tier-bs';
  if (s >= 71) return 'tier-b';
  if (s >= 61) return 'tier-c';
  if (s >= 41) return 'tier-k';
  return 'tier-ks';
}

function countUp(el, target, decimal = false, dur = 700) {
  const t0 = performance.now();
  const step = now => {
    const p = Math.min((now - t0) / dur, 1);
    const e = 1 - Math.pow(1 - p, 3);
    el.textContent = decimal ? (target * e).toFixed(2) : Math.round(target * e);
    if (p < 1) requestAnimationFrame(step);
    else el.textContent = decimal ? target.toFixed(2) : target;
  };
  requestAnimationFrame(step);
}

/* Animate SVG ring 0->score
 * IMPORTANT: ringFill is an SVGCircleElement.
 * Assigning element.className = '...' on SVG throws TypeError in strict mode
 * because SVGAnimatedString is not a plain string setter.
 * Always use setAttribute('class', ...) for SVG elements.
 * Similarly, stroke-dashoffset must be set via setAttribute (not style)
 * because CSS class specificity can override style when the ring-fill class
 * also carries stroke-dashoffset. We drive the animation purely via
 * presentation attributes with a manual RAF loop.
 */
function animateRing(ringFill, scoreVal, tierCls) {
  const CIRC = 157;
  const targetOffset = ((100 - Math.min(Math.max(scoreVal, 0), 100)) / 100) * CIRC;

  // Set class via setAttribute -- safe for SVG elements
  ringFill.setAttribute('class', 'ring-fill ' + tierCls);
  // Reset dashoffset to full circle (empty ring)
  ringFill.setAttribute('stroke-dashoffset', CIRC.toFixed(2));

  const dur = 800;
  const t0  = performance.now();

  function step(now) {
    const p    = Math.min((now - t0) / dur, 1);
    const ease = 1 - Math.pow(1 - p, 3);          // ease-out cubic
    const cur  = CIRC + (targetOffset - CIRC) * ease;
    ringFill.setAttribute('stroke-dashoffset', cur.toFixed(2));
    if (p < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

/* --- STATE ------------------------------------------------- */
function getInstitusi() { return document.querySelector('input[name="institusi"]:checked')?.value || 'polri'; }
function getGender()    { return document.querySelector('input[name="gender"]:checked')?.value || 'pria'; }

/* --- LIVE SCORE PREVIEW ------------------------------------ */
function updateLiveBadge(inputId, badgeId, tableGetter) {
  const el = document.getElementById(inputId);
  const badge = document.getElementById(badgeId);
  if (!el || !badge) return;

  const val = parseFloat(el.value);
  if (!el.value || isNaN(val) || val <= 0) {
    badge.className = 'live-score';
    badge.textContent = '';
    return;
  }
  const table = tableGetter();
  if (!table) return;
  const s = score(val, table);
  const r = rating(s);
  badge.className = 'live-score ls-' + r.cls + ' active';
  badge.textContent = s + ' -- ' + r.label;
}

function getLiveTables() {
  const inst = getInstitusi();
  const gen  = getGender();
  const tb   = ST[inst][gen];
  const puKey = gen === 'wanita' ? 'chinning' : 'pullup';
  return {
    lari:   () => tb.lari,
    pullup: () => tb[puKey],
    situp:  () => tb.situp,
    pushup: () => tb.pushup,
    shuttle:() => tb.shuttle,
    lunges: () => tb.lunges,
    renang: () => {
      if (inst === 'tni') {
        const gaya = document.getElementById('renang-gaya')?.value || 'dada';
        return tb.renang[gaya];
      }
      return tb.renang;
    }
  };
}

function refreshAllBadges() {
  const t = getLiveTables();
  updateLiveBadge('lari',    'badge-lari',    t.lari);
  updateLiveBadge('pullup',  'badge-pullup',  t.pullup);
  updateLiveBadge('situp',   'badge-situp',   t.situp);
  updateLiveBadge('pushup',  'badge-pushup',  t.pushup);
  updateLiveBadge('shuttle', 'badge-shuttle', t.shuttle);
  updateLiveBadge('lunges',  'badge-lunges',  t.lunges);
  updateLiveBadge('renang',  'badge-renang',  t.renang);
}

/* --- UI SYNC ----------------------------------------------- */
function syncUI() {
  const inst    = getInstitusi();
  const gen     = getGender();
  const isTni   = inst === 'tni';
  const isWanita = gen === 'wanita';

  document.body.classList.toggle('tni-mode', isTni);

  document.querySelectorAll('.label-pullup').forEach(el => {
    el.textContent = isWanita ? 'Chinning (rep)' : 'Pull-up (rep)';
  });

  const renangLbl = document.querySelector('.label-renang');
  if (renangLbl) renangLbl.textContent = isTni ? 'Waktu Renang 50m (dtk)' : 'Waktu Renang 25m (dtk)';

  const gayaWrap = document.getElementById('renang-gaya-wrap');
  if (gayaWrap) gayaWrap.classList.toggle('hidden', !isTni);

  // toggle body visibility
  const inclRenang = document.getElementById('include-renang')?.checked;
  const inclLunges = document.getElementById('include-lunges')?.checked;
  const tcRenang = document.getElementById('tc-renang');
  const tcLunges = document.getElementById('tc-lunges');
  if (tcRenang) tcRenang.classList.toggle('active', !!inclRenang);
  if (tcLunges) tcLunges.classList.toggle('active', !!inclLunges);

  refreshAllBadges();
}

/* --- TOGGLE CARD HANDLER ----------------------------------- */
function bindToggle(switchId, cardId) {
  const sw   = document.getElementById(switchId);
  const card = document.getElementById(cardId);
  if (!sw || !card) return;
  sw.addEventListener('change', () => {
    card.classList.toggle('active', sw.checked);
    refreshAllBadges();
  });
}

/* --- VALIDATION -------------------------------------------- */
function validate() {
  const ids = ['lari','pullup','situp','pushup','shuttle'];
  let ok = true;
  ids.forEach(id => {
    const el = document.getElementById(id);
    const v  = parseFloat(el?.value);
    const bad = !el.value || isNaN(v) || v <= 0;
    el?.classList.toggle('input-error', bad);
    if (bad) ok = false;
  });
  return ok;
}

/* --- MAIN CALCULATE ---------------------------------------- */
function calculate() {
  document.querySelectorAll('.input-error').forEach(e => e.classList.remove('input-error'));

  if (!validate()) {
    const btn = document.querySelector('.btn-hitung');
    btn?.classList.add('shake');
    setTimeout(() => btn?.classList.remove('shake'), 600);
    showAlert('Lengkapi semua field wajib terlebih dahulu.');
    return;
  }

  try {
    const inst   = getInstitusi();
    const gen    = getGender();
    const tb     = ST[inst][gen];
    const puKey  = gen === 'wanita' ? 'chinning' : 'pullup';
    const passGrade = inst === 'polri' ? 61 : 41;

    const vLari    = +document.getElementById('lari').value;
    const vPullup  = +document.getElementById('pullup').value;
    const vSitup   = +document.getElementById('situp').value;
    const vPushup  = +document.getElementById('pushup').value;
    const vShuttle = +document.getElementById('shuttle').value;

    const nLari    = score(vLari,    tb.lari);
    const nPullup  = score(vPullup,  tb[puKey]);
    const nSitup   = score(vSitup,   tb.situp);
    const nPushup  = score(vPushup,  tb.pushup);
    const nShuttle = score(vShuttle, tb.shuttle);

    let bItems = [nPullup, nSitup, nPushup, nShuttle];
    let nLunges = null, nRenang = null;

    const inclLunges = document.getElementById('include-lunges')?.checked;
    if (inclLunges) {
      const vL = +document.getElementById('lunges').value || 0;
      nLunges = score(vL, tb.lunges);
      if (nLunges > 0) bItems.push(nLunges);
    }

    const hasZero = nLari === 0 || [nPullup, nSitup, nPushup, nShuttle].some(v => v === 0);
    const avgB    = bItems.reduce((a, b) => a + b, 0) / bItems.length;
    const nilaiAB = (nLari + avgB) / 2;

    let isRenangTMS = false;
    const inclRenang = document.getElementById('include-renang')?.checked;
    if (inclRenang) {
      const vR = +document.getElementById('renang').value || 0;
      let rt;
      if (inst === 'tni') {
        const gaya = document.getElementById('renang-gaya')?.value || 'dada';
        rt = tb.renang[gaya];
      } else {
        rt = tb.renang;
      }
      nRenang = score(vR, rt);
      if (nRenang === 0) isRenangTMS = true;
    }

    const nilaiGab = (inclRenang && nRenang !== null)
      ? nilaiAB * 0.8 + nRenang * 0.2
      : null;

    const isLulus = nilaiAB >= passGrade && !hasZero && !isRenangTMS;

    /* Simpan raw inputs + hasil ke lastResult untuk PDF */
    const vLunges = inclLunges ? (+document.getElementById('lunges').value || 0) : null;
    const vRenang = inclRenang ? (+document.getElementById('renang').value || 0) : null;
    const gayaRenang = (inclRenang && inst === 'tni')
      ? (document.getElementById('renang-gaya')?.value || 'dada') : null;
    lastResult = {
      nama: document.getElementById('nama')?.value?.trim() || '',
      inst: inst.toUpperCase(), gen, passGrade,
      puKey,
      inputs:  { lari: vLari, pullup: vPullup, situp: vSitup, pushup: vPushup,
                 shuttle: vShuttle, lunges: vLunges, renang: vRenang, gayaRenang },
      scores:  { lari: nLari, pullup: nPullup, situp: nSitup, pushup: nPushup,
                 shuttle: nShuttle, lunges: nLunges, renang: nRenang },
      nilaiAB, nilaiGab, isLulus, inclLunges, inclRenang
    };

    renderResults({
      nLari, nPullup, nSitup, nPushup, nShuttle,
      nLunges, nRenang, nilaiAB, nilaiGab,
      hasZero, isRenangTMS, isLulus, passGrade,
      inclLunges, inclRenang, gen
    });

    /* Pre-generate PDF di background -> ketika user klik tombol,
     * download/share terjadi sinkron langsung dari klik tanpa
     * await, sehingga user-activation HP tidak hilang. */
    invalidateCachedPdf();
    setTimeout(() => preGeneratePdf(lastResult), 150);
  } catch (err) {
    console.error(err);
    showAlert('Terjadi kesalahan, periksa kembali input.');
  }
}

/* --- RENDER RESULTS ---------------------------------------- */
function renderResults(d) {
  const resultsEl = document.getElementById('results');
  if (!resultsEl) return;
  resultsEl.classList.remove('hidden');

  // Score items
  const items = [
    { id:'s-lari',    val: d.nLari,    label:'Lari 12 Min' },
    { id:'s-pullup',  val: d.nPullup,  label: d.gen === 'wanita' ? 'Chinning' : 'Pull-up' },
    { id:'s-situp',   val: d.nSitup,   label:'Sit-up' },
    { id:'s-pushup',  val: d.nPushup,  label:'Push-up' },
    { id:'s-shuttle', val: d.nShuttle, label:'Shuttle Run' },
  ];
  if (d.inclLunges && d.nLunges !== null) {
    items.push({ id:'s-lunges', val: d.nLunges, label:'Lunges' });
    document.getElementById('si-lunges')?.classList.remove('hidden');
  } else {
    document.getElementById('si-lunges')?.classList.add('hidden');
  }
  if (d.inclRenang && d.nRenang !== null) {
    items.push({ id:'s-renang', val: d.nRenang, label:'Renang' });
    document.getElementById('si-renang')?.classList.remove('hidden');
  } else {
    document.getElementById('si-renang')?.classList.add('hidden');
  }

  items.forEach(({ id, val, label }) => {
    const item  = document.getElementById(id);
    if (!item) return;
    const tc = tierClass(val);
    const r  = rating(val);
    const numEl  = item.querySelector('.ring-num');
    const fillEl = item.querySelector('.ring-fill');
    const lblEl  = item.querySelector('.score-label');
    const badgeEl= item.querySelector('.rating-badge');

    if (numEl)  { numEl.className = 'ring-num ' + tc; countUp(numEl, val); }
    if (fillEl) animateRing(fillEl, val, tc);
    if (lblEl)  lblEl.textContent = label;
    if (badgeEl){ badgeEl.textContent = r.label; badgeEl.className = 'rating-badge rating-' + r.cls; }
  });

  // Nilai A&B gauge
  const gcVal  = document.getElementById('gc-nilai-ab');
  const gcBadge= document.getElementById('gc-badge-ab');
  const gcSub  = document.getElementById('gc-sub-ab');
  if (gcVal)   countUp(gcVal, d.nilaiAB, true);
  if (gcBadge) { const r = rating(d.nilaiAB); gcBadge.textContent = r.label; }
  if (gcSub)   gcSub.textContent = `Passing grade: ${d.passGrade}`;

  // Nilai gabungan
  const gabCard = document.getElementById('gc-gabungan');
  if (d.nilaiGab !== null && gabCard) {
    gabCard.classList.remove('hidden');
    const gv = document.getElementById('gc-nilai-gab');
    if (gv) countUp(gv, d.nilaiGab, true);
    const gs = document.getElementById('gc-sub-gab');
    if (gs) gs.textContent = 'A&B 80% + Renang 20%';
  } else {
    gabCard?.classList.add('hidden');
  }

  // Progress bar
  const displayVal = d.nilaiGab ?? d.nilaiAB;
  const pct = Math.min((displayVal / d.passGrade) * 100, 100);
  const progFill = document.getElementById('prog-fill');
  const progPct  = document.getElementById('prog-pct');
  const progTxt  = document.getElementById('prog-txt');
  if (progFill) {
    setTimeout(() => {
      progFill.style.width = pct + '%';
      progFill.classList.toggle('achieved', displayVal >= d.passGrade);
    }, 200);
  }
  if (progPct) progPct.textContent = displayVal.toFixed(1);
  if (progTxt) progTxt.textContent = `/ ${d.passGrade} (passing grade)`;

  // Status
  const statusEl = document.getElementById('status-bar');
  if (statusEl) {
    statusEl.className = 'status-bar ' + (d.isLulus ? 'status-ms' : 'status-tms');
    let reason = '';
    if (!d.isLulus) {
      if (d.hasZero)             reason = ' -- Ada item bernilai 0';
      else if (d.isRenangTMS)    reason = ' -- Renang tidak memenuhi syarat';
      else if (d.nilaiAB < d.passGrade) reason = ` -- Nilai di bawah ${d.passGrade}`;
    }
    statusEl.innerHTML = d.isLulus
      ? `<span class="status-icon"> </span><span>Memenuhi Syarat (MS)</span>`
      : `<span class="status-icon"> </span><span>Tidak Memenuhi Syarat${reason}</span>`;
  }

  // Scroll to results
  setTimeout(() => resultsEl.scrollIntoView({ behavior:'smooth', block:'start' }), 100);
}

/* --- PDF GENERATION (pure jsPDF - synchronous, works on all devices) --
 *
 * Arsitektur v5:
 * - _buildPdfArtifact(R) sekarang SINKRON menggunakan jsPDF drawing API langsung.
 *   Tidak ada html2canvas, tidak ada canvas rendering, tidak ada async wait.
 *   Keuntungan: user-activation tidak expire -> navigator.share() & anchor.click()
 *   selalu berhasil di HP (Android Chrome, iOS Safari, Firefox Android).
 */

function _buildPdfArtifact(R) {
  if (!window.jspdf) throw new Error('jsPDF belum dimuat');
  const { jsPDF } = window.jspdf;

  const sc   = R.scores;
  const inp  = R.inputs;
  const doc  = new jsPDF('p', 'mm', 'a4');
  const PW   = 210, M = 18, CW = PW - M * 2;

  /* -- Colour helpers ----------------------------------------- */
  const C = {
    maroon  : [92,  28,  13],
    maroon2 : [168, 64,  32],
    maroon3 : [120, 38,  20],
    gold    : [212, 137, 42],
    dark    : [26,  15,  7],
    gray    : [120, 100, 85],
    lgray   : [200, 185, 170],
    cream   : [253, 240, 232],
    white   : [255, 255, 255],
  };
  function scoreRgb(s) {
    if (s >= 81) return [5,   150, 105];
    if (s >= 71) return [37,  99,  235];
    if (s >= 61) return [217, 119, 6];
    if (s >= 41) return [234, 88,  12];
    return             [220, 38,  38];
  }
  function ratingLbl(s) {
    if (s >= 81) return 'Baik Sekali';
    if (s >= 71) return 'Baik';
    if (s >= 61) return 'Cukup';
    if (s >= 41) return 'Kurang';
    return 'Kurang Sekali';
  }
  function fill(...c) { doc.setFillColor(...c); }
  function stroke(...c) { doc.setDrawColor(...c); }
  function txt(color, ...c) { doc.setTextColor(...c); }

  /* -- Header ------------------------------------------------- */
  fill(...C.maroon); doc.rect(0, 0, PW, 46, 'F');
  fill(...C.maroon3); doc.rect(PW * 0.55, 0, PW * 0.5, 46, 'F');
  fill(...C.gold); doc.rect(0, 43, PW, 1.2, 'F');

  doc.setTextColor(...C.white);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(6.5);
  doc.text('PERKASA MULIA TRAINING CENTER  -  BIMBEL & BINJAS POLRI & TNI  -  BOGOR', PW / 2, 8.5, { align: 'center' });

  doc.setFontSize(17);
  doc.text('HASIL TES KESAMAPTAAN JASMANI', PW / 2, 20, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  const dateStr = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  doc.text(dateStr, PW / 2, 28, { align: 'center' });

  doc.setFontSize(7.5);
  doc.setTextColor(255, 215, 150);
  const hdrParts = [R.inst, R.gen === 'wanita' ? 'Wanita' : 'Pria'];
  if (R.nama) hdrParts.push(R.nama);
  doc.text(hdrParts.join('  |  '), PW / 2, 36.5, { align: 'center' });

  let y = 51;

  /* -- Nama card --------------------------------------------- */
  if (R.nama) {
    fill(...C.cream); doc.roundedRect(M, y, CW, 14, 2, 2, 'F');
    stroke(...C.gold); doc.setLineWidth(0.4); doc.roundedRect(M, y, CW, 14, 2, 2, 'S'); doc.setLineWidth(0.2);
    doc.setTextColor(...C.gray); doc.setFont('helvetica', 'normal'); doc.setFontSize(6.5);
    doc.text('NAMA PESERTA', M + 6, y + 5.5);
    doc.setTextColor(...C.dark); doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
    doc.text(R.nama, M + 6, y + 12);
    y += 19;
  }

  /* -- Info chips -------------------------------------------- */
  const chips = [
    { label: 'INSTITUSI',     val: R.inst },
    { label: 'JENIS KELAMIN', val: R.gen === 'wanita' ? 'Wanita' : 'Pria' },
    { label: 'PASSING GRADE', val: String(R.passGrade) },
  ];
  const chipW = (CW - (chips.length - 1) * 5) / chips.length;
  chips.forEach((c, i) => {
    const cx = M + i * (chipW + 5);
    fill(245, 238, 230); doc.roundedRect(cx, y, chipW, 16, 2, 2, 'F');
    doc.setTextColor(...C.gray); doc.setFont('helvetica', 'normal'); doc.setFontSize(6.5);
    doc.text(c.label, cx + 5, y + 5.5);
    doc.setTextColor(...C.maroon); doc.setFont('helvetica', 'bold'); doc.setFontSize(11);
    doc.text(c.val, cx + 5, y + 13);
  });
  y += 21;

  /* -- Score table header ------------------------------------ */
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8.5); doc.setTextColor(...C.maroon2);
  doc.text('NILAI PER ITEM TES', M, y);
  y += 4;

  const K = [0, CW * 0.44, CW * 0.62, CW * 0.79];
  fill(...C.maroon); doc.roundedRect(M, y, CW, 8, 1.5, 1.5, 'F');
  doc.setTextColor(...C.white); doc.setFont('helvetica', 'bold'); doc.setFontSize(7.5);
  doc.text('ITEM TES',  M + K[0] + 4, y + 5.5);
  doc.text('INPUT',     M + K[1] + 3, y + 5.5);
  doc.text('NILAI',     M + K[2] + 3, y + 5.5);
  doc.text('PREDIKAT',  M + K[3] + 2, y + 5.5);
  y += 8;

  /* -- Score table rows -------------------------------------- */
  const pullLabel = R.puKey === 'chinning' ? 'Chinning' : 'Pull-up';
  const rows = [
    ['Lari 12 Menit', inp.lari   + ' m',   sc.lari,    false],
    [pullLabel,        inp.pullup + ' rep', sc.pullup,  false],
    ['Sit-up',         inp.situp  + ' rep', sc.situp,   false],
    ['Push-up',        inp.pushup + ' rep', sc.pushup,  false],
    ['Shuttle Run',    inp.shuttle + ' dtk',sc.shuttle, false],
  ];
  if (R.inclLunges && sc.lunges !== null)
    rows.push(['Lunges', inp.lunges + ' rep', sc.lunges, true]);
  if (R.inclRenang && sc.renang !== null) {
    const dist   = R.inst === 'TNI' ? '50m' : '25m';
    const gLabel = inp.gayaRenang ? ' (' + inp.gayaRenang + ')' : '';
    rows.push(['Renang ' + dist + gLabel, inp.renang + ' dtk', sc.renang, true]);
  }

  rows.forEach(([label, inputVal, sVal, isOpt], idx) => {
    const rowH = 9.5;
    const isEven = idx % 2 === 0;
    fill(isEven ? 250 : 255, isEven ? 246 : 255, isEven ? 242 : 255);
    doc.rect(M, y, CW, rowH, 'F');
    if (sVal !== null) { fill(...scoreRgb(sVal)); doc.rect(M, y, 3, rowH, 'F'); }
    doc.setTextColor(...C.dark); doc.setFont('helvetica', isOpt ? 'italic' : 'normal'); doc.setFontSize(8.5);
    doc.text(label, M + K[0] + 6, y + 6.5);
    doc.setFont('helvetica', 'normal'); doc.setTextColor(...C.gray); doc.setFontSize(8);
    doc.text(String(inputVal), M + K[1] + 3, y + 6.5);
    if (sVal !== null) {
      doc.setFont('helvetica', 'bold'); doc.setFontSize(10); doc.setTextColor(...scoreRgb(sVal));
      doc.text(String(sVal), M + K[2] + 3, y + 6.8);
      doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5); doc.setTextColor(...C.gray);
      doc.text(ratingLbl(sVal), M + K[3] + 2, y + 6.5);
    }
    stroke(...C.lgray); doc.line(M, y + rowH, M + CW, y + rowH);
    y += rowH;
  });

  y += 6;

  /* -- Final score cards ------------------------------------- */
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8.5); doc.setTextColor(...C.maroon2);
  doc.text('NILAI AKHIR', M, y);
  y += 4;

  const hasGab = R.nilaiGab !== null;
  const cardW  = hasGab ? (CW - 6) / 2 : CW;
  const cardH  = 28;

  // A&B card
  fill(...C.maroon); doc.roundedRect(M, y, cardW, cardH, 2.5, 2.5, 'F');
  fill(...C.maroon3); doc.roundedRect(M + cardW * 0.5, y, cardW * 0.5, cardH, 2.5, 2.5, 'F');
  doc.setTextColor(...C.white);
  doc.setFont('helvetica', 'normal'); doc.setFontSize(6.5);
  doc.text('NILAI SAMAPTA A & B', M + cardW / 2, y + 7, { align: 'center' });
  doc.setFont('helvetica', 'bold'); doc.setFontSize(24);
  doc.text(R.nilaiAB.toFixed(2), M + cardW / 2, y + 20.5, { align: 'center' });
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7);
  doc.text(ratingLbl(R.nilaiAB), M + cardW / 2, y + 26, { align: 'center' });

  // Gabungan card
  if (hasGab) {
    const gx = M + cardW + 6;
    fill(...C.cream); doc.roundedRect(gx, y, cardW, cardH, 2.5, 2.5, 'F');
    stroke(...C.maroon2); doc.setLineWidth(0.5); doc.roundedRect(gx, y, cardW, cardH, 2.5, 2.5, 'S'); doc.setLineWidth(0.2);
    doc.setTextColor(...C.gray); doc.setFont('helvetica', 'normal'); doc.setFontSize(6);
    doc.text('NILAI GABUNGAN  (A&B 80% + RENANG 20%)', gx + cardW / 2, y + 7, { align: 'center' });
    doc.setTextColor(...C.maroon); doc.setFont('helvetica', 'bold'); doc.setFontSize(24);
    doc.text(R.nilaiGab.toFixed(2), gx + cardW / 2, y + 20.5, { align: 'center' });
    doc.setFont('helvetica', 'normal'); doc.setFontSize(7); doc.setTextColor(...C.gray);
    doc.text(ratingLbl(R.nilaiGab), gx + cardW / 2, y + 26, { align: 'center' });
  }

  y += cardH + 7;

  /* -- Status bar -------------------------------------------- */
  const isMS  = R.isLulus;
  const sBg   = isMS ? [232, 253, 245] : [255, 241, 242];
  const sBd   = isMS ? [110, 220, 170] : [250, 180, 190];
  const sFg   = isMS ? [5,   78,  55]  : [120, 25,  25];
  const sTx   = isMS ? '[LULUS]  MEMENUHI SYARAT  (MS)' : '[GAGAL]  TIDAK MEMENUHI SYARAT  (TMS)';
  fill(...sBg); doc.roundedRect(M, y, CW, 15, 3, 3, 'F');
  stroke(...sBd); doc.setLineWidth(0.6); doc.roundedRect(M, y, CW, 15, 3, 3, 'S'); doc.setLineWidth(0.2);
  doc.setFont('helvetica', 'bold'); doc.setFontSize(12.5); doc.setTextColor(...sFg);
  doc.text(sTx, PW / 2, y + 9.8, { align: 'center' });
  y += 20;

  /* -- Note -------------------------------------------------- */
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7.5); doc.setTextColor(...C.gray);
  doc.text(
    'Passing grade ' + R.inst + ': ' + R.passGrade + '  -  Setiap item tidak boleh bernilai 0',
    PW / 2, y, { align: 'center' }
  );
  y += 8;

  /* -- Footer ------------------------------------------------ */
  fill(...C.gold); doc.rect(M, y, CW, 0.7, 'F'); y += 5;
  doc.setFont('helvetica', 'bold'); doc.setFontSize(8.5); doc.setTextColor(...C.maroon);
  doc.text('Perkasa Mulia Training Center', PW / 2, y, { align: 'center' });
  y += 5;
  doc.setFont('helvetica', 'normal'); doc.setFontSize(7); doc.setTextColor(...C.gray);
  doc.text('Bogor, Jawa Barat  -  bimbelperkasa.id  -  Kalkulator Samapta Polri & TNI 2026', PW / 2, y, { align: 'center' });
  y += 4;
  doc.setFontSize(6.5);
  doc.text('Dokumen ini dicetak dari simulasi kalkulator. Nilai bukan merupakan hasil tes resmi institusi.', PW / 2, y, { align: 'center' });

  /* -- Build artifact ---------------------------------------- */
  const namePart = R.nama ? R.nama.replace(/[^a-zA-Z0-9]+/g, '_') + '-' : '';
  const datePart = new Date().toISOString().slice(0, 10);
  const filename = 'Hasil-Samapta-' + namePart + R.inst + '-' + (R.gen === 'wanita' ? 'Wanita' : 'Pria') + '-' + datePart + '.pdf';
  const blob = doc.output('blob');
  const file = new File([blob], filename, { type: 'application/pdf' });

  return { pdf: doc, blob, file, filename };
}


/* --- PDF CACHE & PRE-GENERATION ----------------------------- */
function pdfSignature(R) {
  // Tanda-tangan ringan: kalau hasil berubah, signature beda -> cache invalid
  return [R.inst, R.gen, R.nama, R.nilaiAB, R.nilaiGab, R.isLulus,
          R.scores.lari, R.scores.pullup, R.scores.situp, R.scores.pushup,
          R.scores.shuttle, R.scores.lunges, R.scores.renang].join('|');
}

function invalidateCachedPdf() {
  if (cachedPdf?.blobUrl) URL.revokeObjectURL(cachedPdf.blobUrl);
  cachedPdf = null;
}

function preGeneratePdf(R) {
  // _buildPdfArtifact is now synchronous - pre-generate immediately after calculate()
  if (!R || pdfBuilding) return;
  if (cachedPdf && cachedPdf.signature === pdfSignature(R)) return;
  pdfBuilding = true;
  try {
    const sig    = pdfSignature(R);
    const a      = _buildPdfArtifact(R);   // sync - no await
    const blobUrl = URL.createObjectURL(a.blob);
    if (cachedPdf && cachedPdf.blobUrl) URL.revokeObjectURL(cachedPdf.blobUrl);
    cachedPdf = { ...a, blobUrl, signature: sig };
  } catch (err) {
    console.warn('[preGeneratePdf]', err);
  } finally {
    pdfBuilding = false;
  }
}

/* --- DOWNLOAD PDF ------------------------------------------- */
function downloadPDF() {
  if (!lastResult) { showAlert('Hitung nilai dulu sebelum membuat PDF.'); return; }
  const btn = document.getElementById('btn-download');
  try {
    const a      = _buildPdfArtifact(lastResult);  // synchronous - no await needed
    const blobUrl = URL.createObjectURL(a.blob);
    cachedPdf = { ...a, blobUrl, signature: pdfSignature(lastResult) };
    forceDownload(blobUrl, a.filename);
    flashBtn(btn, 'Download', 'PDF Diunduh!');
  } catch (err) {
    console.error('[downloadPDF]', err);
    showAlert('Gagal membuat PDF: ' + (err.message || 'error'));
  }
}

/* --- SHARE PDF (Web Share API) ------------------------------ */
function sharePDF() {
  if (!lastResult) { showAlert('Hitung nilai dulu sebelum membuat PDF.'); return; }
  const btn = document.getElementById('btn-pdf');
  // Build PDF synchronously on the user-click stack frame.
  // User-activation is still alive because we have not awaited anything yet.
  let artifact;
  try {
    artifact = _buildPdfArtifact(lastResult);  // sync - no await
    const blobUrl = URL.createObjectURL(artifact.blob);
    cachedPdf = { ...artifact, blobUrl, signature: pdfSignature(lastResult) };
  } catch (err) {
    console.error('[sharePDF build]', err);
    showAlert('Gagal membuat PDF: ' + (err.message || 'error'));
    return;
  }
  // _doShare may be async (navigator.share) but the user-activation survives
  // because we trigger it immediately without any intermediate await.
  _doShare(btn, cachedPdf);
}

/* Eksekusi share dengan fallback chain */
async function _doShare(btn, pdfArt, skipFlash) {
  const R = lastResult;
  const shareData = {
    title: 'Hasil Samapta - Perkasa Mulia Training Center',
    text:  `Hasil tes kesamaptaan jasmani ${R.inst} - ${R.gen === 'wanita' ? 'Wanita' : 'Pria'}` +
           (R.nama ? ' - ' + R.nama : '') +
           ` | Nilai: ${(R.nilaiGab ?? R.nilaiAB).toFixed(2)} | ${R.isLulus ? 'MS' : 'TMS'}`,
    files: [pdfArt.file]
  };

  // Tier A -- Web Share API + files (HP modern)
  if (navigator.canShare && navigator.canShare({ files: [pdfArt.file] }) && navigator.share) {
    try {
      await navigator.share(shareData);
      flashBtn(btn, 'PDF', 'PDF Dibagikan!');
      return;
    } catch (err) {
      if (err && err.name === 'AbortError') {
        // User batal -- kembalikan tombol
        if (btn) {
          btn.disabled = false; btn.style.opacity = '';
          btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Bagikan PDF';
        }
        return;
      }
      // err lain -> fallback ke download
    }
  }

  // Tier B -- Web Share tidak support -> trigger download/buka via anchor
  // (anchor click lebih reliabel daripada window.open di HP)
  forceDownload(pdfArt.blobUrl, pdfArt.filename);
  flashBtn(btn, 'PDF', 'PDF Diunduh!');
}

/* Helper: jalankan async work + handling tombol loading state */
async function _runWithLoading(btn, kind, asyncFn) {
  const orig = btn ? btn.innerHTML : '';
  setBtnLoading(btn, 'Membuat PDF...');
  try {
    const successMsg = await asyncFn();
    if (successMsg) flashBtn(btn, kind, successMsg);
  } catch (err) {
    console.error('[PDF]', err);
    showAlert('Gagal membuat PDF: ' + (err.message || 'unknown error'));
    if (btn) { btn.innerHTML = orig; btn.disabled = false; btn.style.opacity = ''; }
  }
}

/* --- PDF HELPER: Set tombol ke loading state --------------- */
function setBtnLoading(btn, label) {
  if (!btn) return;
  btn.disabled = true;
  btn.style.opacity = '0.85';
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin" aria-hidden="true"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> ' + label;
}

/* --- PDF HELPER: Force download via anchor link --------------
 * Strategi paling reliabel di semua device:
 *   * Android Chrome  -> atribut `download` memicu download asli
 *   * iOS Safari      -> atribut `download` diabaikan; karena
 *                       pakai target=_blank PDF dibuka di tab/viewer
 *                       baru, user bisa save/share dari sana.
 *   * Desktop         -> download langsung. */
function forceDownload(blobUrl, filename) {
  // Strategy: anchor with download attr - works on Android Chrome & desktop.
  // iOS Safari ignores download attr on blobs, opens in viewer (user can share from there).
  const a = document.createElement('a');
  a.href     = blobUrl;
  a.download = filename;
  a.target   = '_blank';
  a.rel      = 'noopener noreferrer';
  a.style.cssText = 'position:fixed;top:-100px;left:-100px;opacity:0;';
  document.body.appendChild(a);
  a.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
  setTimeout(() => { try { document.body.removeChild(a); } catch(e){} }, 300);
}

/* --- PDF HELPER: Tombol feedback animation ----------------- */
function flashBtn(btn, kind, label) {
  if (!btn) return;
  btn.disabled = false;
  btn.style.opacity = '';
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ' + label;
  btn.style.background = '#d1fae5';
  btn.style.borderColor = '#a7f3d0';
  btn.style.color = '#064e3b';
  setTimeout(() => {
    if (kind === 'Download') {
      btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download PDF';
    } else {
      btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Bagikan PDF';
    }
    btn.removeAttribute('style');
  }, 3000);
}

/* --- PDF HELPER: Bangun template HTML untuk dirender ------- */
function buildPdfHtml(R) {
  const sc  = R.scores;
  const inp = R.inputs;
  const isMS = R.isLulus;

  const ratingLabel = (s) => {
    if (s >= 81) return 'Baik Sekali';
    if (s >= 71) return 'Baik';
    if (s >= 61) return 'Cukup';
    if (s >= 41) return 'Kurang';
    return 'Kurang Sekali';
  };
  const tierColor = (s) => {
    if (s >= 81) return '#059669';
    if (s >= 71) return '#2563eb';
    if (s >= 61) return '#d97706';
    if (s >= 41) return '#ea580c';
    return '#dc2626';
  };

  const pullLabel = R.puKey === 'chinning' ? 'Chinning' : 'Pull-up';
  const rows = [
    ['Lari 12 Menit',  `${inp.lari} m`,        sc.lari,    false],
    [pullLabel,         `${inp.pullup} rep`,    sc.pullup,  false],
    ['Sit-up',          `${inp.situp} rep`,     sc.situp,   false],
    ['Push-up',         `${inp.pushup} rep`,    sc.pushup,  false],
    ['Shuttle Run',     `${inp.shuttle} dtk`,   sc.shuttle, false]
  ];
  if (R.inclLunges && sc.lunges !== null)
    rows.push(['Lunges', `${inp.lunges} rep`, sc.lunges, true]);
  if (R.inclRenang && sc.renang !== null) {
    const gLabel = inp.gayaRenang ? ` (gaya ${inp.gayaRenang})` : '';
    const dist = R.inst === 'TNI' ? '50m' : '25m';
    rows.push([`Renang ${dist}${gLabel}`, `${inp.renang} dtk`, sc.renang, true]);
  }

  const dateStr = new Date().toLocaleDateString('id-ID', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  });

  // Theme: kecuali TNI gunakan palette hijau
  const isTni = R.inst === 'TNI';
  const c = isTni
    ? { dark: '#052e16', mid: '#065f46', light: '#10b981', accent: '#fbbf24', cream: '#ecfdf5', tint: '#d1fae5' }
    : { dark: '#3b1208', mid: '#7c2610', light: '#a84020', accent: '#d4892a', cream: '#fdf0e8', tint: '#fde8d9' };

  const wrap = document.createElement('div');
  wrap.style.cssText = `
    position: absolute;
    top: 0; left: -9999px;
    width: 794px;
    background: #ffffff;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    color: #1a0f07;
    box-sizing: border-box;
    z-index: -1;
  `;

  const rowsHtml = rows.map((r, idx) => {
    const [label, inputVal, sVal, isOpt] = r;
    const bg = idx % 2 === 0 ? '#faf7f3' : '#ffffff';
    const tc = sVal !== null ? tierColor(sVal) : '#9ca3af';
    return `
      <tr style="background:${bg}">
        <td style="padding:13px 12px 13px 18px;border-left:4px solid ${tc};font-weight:${isOpt ? 500 : 600};font-size:14px;color:#1a0f07;${isOpt ? 'font-style:italic;' : ''}">${label}</td>
        <td style="padding:13px 12px;font-size:13px;color:#4a3728">${inputVal || '--'}</td>
        <td style="padding:13px 12px;font-weight:800;font-size:18px;color:${tc};text-align:center">${sVal !== null ? sVal : '--'}</td>
        <td style="padding:13px 18px 13px 12px;font-size:12px;color:#4a3728;text-align:right;font-weight:500">${sVal !== null ? ratingLabel(sVal) : '--'}</td>
      </tr>
    `;
  }).join('');

  const hasGab = R.nilaiGab !== null;
  const finalCardsHtml = `
    <div style="display:flex;gap:12px;margin-top:8px">
      <div style="flex:1;background:linear-gradient(135deg,${c.mid} 0%,${c.dark} 100%);border-radius:14px;padding:20px 22px;color:#ffffff;box-shadow:0 4px 14px rgba(0,0,0,.12)">
        <div style="font-size:11px;letter-spacing:1.2px;font-weight:600;opacity:.85;margin-bottom:6px">NILAI SAMAPTA A &amp; B</div>
        <div style="font-size:46px;font-weight:800;line-height:1;letter-spacing:-1px">${R.nilaiAB.toFixed(2)}</div>
        <div style="margin-top:8px;display:inline-block;padding:4px 12px;background:rgba(255,255,255,.18);border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.5px">${ratingLabel(R.nilaiAB).toUpperCase()}</div>
      </div>
      ${hasGab ? `
      <div style="flex:1;background:${c.cream};border:2px solid ${c.accent};border-radius:14px;padding:20px 22px;color:${c.dark}">
        <div style="font-size:11px;letter-spacing:1px;font-weight:600;color:${c.mid};margin-bottom:6px">NILAI GABUNGAN <span style="font-weight:400;opacity:.8;letter-spacing:0">(A&amp;B 80% + Renang 20%)</span></div>
        <div style="font-size:46px;font-weight:800;line-height:1;letter-spacing:-1px;color:${c.mid}">${R.nilaiGab.toFixed(2)}</div>
        <div style="margin-top:8px;display:inline-block;padding:4px 12px;background:${c.tint};border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.5px;color:${c.dark}">${ratingLabel(R.nilaiGab).toUpperCase()}</div>
      </div>
      ` : ''}
    </div>
  `;

  const statusHtml = isMS
    ? `<div style="margin-top:18px;background:#ecfdf5;border:2px solid #6ee7b7;border-radius:12px;padding:14px;text-align:center;color:#054e37;font-weight:700;font-size:16px;letter-spacing:.5px">
          &nbsp; LULUS -- MEMENUHI SYARAT (MS)
       </div>`
    : `<div style="margin-top:18px;background:#fff1f2;border:2px solid #fda4af;border-radius:12px;padding:14px;text-align:center;color:#7f1d1d;font-weight:700;font-size:16px;letter-spacing:.5px">
          &nbsp; TIDAK MEMENUHI SYARAT (TMS)
       </div>`;

  const namaCardHtml = R.nama ? `
    <div style="background:${c.cream};border:1.5px solid ${c.accent};border-radius:10px;padding:11px 18px;margin-bottom:14px">
      <div style="font-size:10px;letter-spacing:1.2px;color:${c.mid};font-weight:600;margin-bottom:3px">NAMA PESERTA</div>
      <div style="font-size:18px;font-weight:700;color:#1a0f07">${escapeHtml(R.nama)}</div>
    </div>
  ` : '';

  /* Logo: pakai inline SVG kalau sudah ter-preload (paling reliabel
   * untuk html2canvas). Fallback ke <img src=...> kalau belum siap. */
  const logoMarkup = LOGO_SVG_INLINE
    ? `<div style="width:54px;height:54px;flex-shrink:0;filter:drop-shadow(0 2px 6px rgba(0,0,0,.35))">${LOGO_SVG_INLINE.replace(/<svg([^>]*)>/, '<svg$1 width="54" height="54" style="display:block">')}</div>`
    : `<img src="img/logo.svg" alt="" style="width:54px;height:54px;object-fit:contain;flex-shrink:0;filter:drop-shadow(0 2px 6px rgba(0,0,0,.35))" onerror="this.style.display='none'">`;

  wrap.innerHTML = `
    <div style="background:linear-gradient(135deg,${c.dark} 0%,${c.mid} 55%,${c.light} 100%);padding:24px 36px 22px;color:#ffffff;position:relative">
      <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:12px">
        ${logoMarkup}
        <div style="text-align:left;line-height:1.1">
          <div style="font-size:18px;font-weight:800;letter-spacing:.6px;color:#ffffff">PERKASA MULIA</div>
          <div style="font-size:10px;font-weight:600;letter-spacing:1.5px;color:${c.accent};margin-top:2px">TRAINING CENTER   BOGOR</div>
        </div>
      </div>
      <div style="font-size:28px;font-weight:800;letter-spacing:-.5px;text-align:center;line-height:1.15">HASIL TES KESAMAPTAAN JASMANI</div>
      <div style="font-size:12px;text-align:center;margin-top:6px;opacity:.95">${dateStr}</div>
      <div style="margin-top:8px;text-align:center;font-size:11px;color:${c.accent};font-weight:600;letter-spacing:.6px">${R.inst}     ${R.gen === 'wanita' ? 'WANITA' : 'PRIA'}${R.nama ? '     ' + escapeHtml(R.nama).toUpperCase() : ''}</div>
      <div style="position:absolute;left:0;right:0;bottom:0;height:5px;background:${c.accent}"></div>
    </div>

    <div style="padding:22px 36px 26px">
      ${namaCardHtml}

      <div style="display:flex;gap:10px;margin-bottom:18px">
        <div style="flex:1;background:#f8f4ee;border-radius:10px;padding:11px 14px">
          <div style="font-size:10px;letter-spacing:1px;color:#8a7265;font-weight:600">INSTITUSI</div>
          <div style="font-size:16px;font-weight:700;color:${c.mid};margin-top:2px">${R.inst}</div>
        </div>
        <div style="flex:1;background:#f8f4ee;border-radius:10px;padding:11px 14px">
          <div style="font-size:10px;letter-spacing:1px;color:#8a7265;font-weight:600">JENIS KELAMIN</div>
          <div style="font-size:16px;font-weight:700;color:${c.mid};margin-top:2px">${R.gen === 'wanita' ? 'Wanita' : 'Pria'}</div>
        </div>
        <div style="flex:1;background:#f8f4ee;border-radius:10px;padding:11px 14px">
          <div style="font-size:10px;letter-spacing:1px;color:#8a7265;font-weight:600">PASSING GRADE</div>
          <div style="font-size:16px;font-weight:700;color:${c.mid};margin-top:2px">${R.passGrade}</div>
        </div>
      </div>

      <div style="font-size:11px;letter-spacing:1.2px;color:${c.mid};font-weight:700;margin-bottom:8px">NILAI PER ITEM TES</div>
      <table style="width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)">
        <thead>
          <tr style="background:${c.mid};color:#ffffff">
            <th style="padding:10px 18px;text-align:left;font-size:11px;letter-spacing:1px;font-weight:700">ITEM TES</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;letter-spacing:1px;font-weight:700">INPUT</th>
            <th style="padding:10px 12px;text-align:center;font-size:11px;letter-spacing:1px;font-weight:700">NILAI</th>
            <th style="padding:10px 18px;text-align:right;font-size:11px;letter-spacing:1px;font-weight:700">PREDIKAT</th>
          </tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
      </table>

      <div style="font-size:11px;letter-spacing:1.2px;color:${c.mid};font-weight:700;margin-top:22px;margin-bottom:6px">NILAI AKHIR</div>
      ${finalCardsHtml}

      ${statusHtml}

      <div style="text-align:center;margin-top:14px;font-size:11px;color:#8a7265">
        Passing grade ${R.inst}: <strong style="color:${c.mid}">${R.passGrade}</strong>     Setiap item tidak boleh bernilai 0
      </div>

      <hr style="border:none;border-top:1.5px solid ${c.accent};margin:18px 0 14px">

      <div style="text-align:center">
        <div style="font-size:14px;font-weight:700;color:${c.mid};letter-spacing:.3px">Perkasa Mulia Training Center</div>
        <div style="font-size:11px;color:#8a7265;margin-top:2px">Bogor, Jawa Barat     bimbelperkasa.id     Kalkulator Samapta Polri &amp; TNI 2026</div>
        <div style="font-size:9.5px;color:#b8a898;margin-top:6px;font-style:italic">Dokumen ini dicetak dari simulasi kalkulator. Nilai bukan merupakan hasil tes resmi institusi.</div>
      </div>
    </div>
  `;
  return wrap;
}

/* --- PDF HELPER: HTML escape ------------------------------- */
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[ch]);
}


/* --- RESET ------------------------------------------------- */
function resetForm() {
  document.querySelectorAll('input[type="number"]').forEach(el => { el.value = ''; el.classList.remove('input-error'); });
  document.getElementById('results')?.classList.add('hidden');
  document.querySelectorAll('.live-score').forEach(el => { el.className = 'live-score'; el.textContent = ''; });
  // reset prog bar
  const pf = document.getElementById('prog-fill');
  if (pf) pf.style.width = '0%';
  // invalidasi cache PDF supaya tidak share hasil lama
  invalidateCachedPdf();
  lastResult = null;
}

/* --- ALERT ------------------------------------------------- */
function showAlert(msg) {
  const el = document.getElementById('form-alert');
  if (!el) return;
  el.textContent = msg;
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 4000);
}

/* --- INIT -------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  syncUI();

  /* Preload logo SVG ke memory untuk dipakai inline di PDF
   * (paling reliabel untuk html2canvas -- tidak race image load) */
  preloadLogoSVG();

  // Radio changes -- invalidasi cache PDF & sembunyikan hasil lama
  document.querySelectorAll('input[name="institusi"], input[name="gender"]').forEach(el => {
    el.addEventListener('change', () => {
      syncUI();
      document.getElementById('results')?.classList.add('hidden');
      invalidateCachedPdf();
    });
  });

  // Toggle switches
  bindToggle('include-renang', 'tc-renang');
  bindToggle('include-lunges', 'tc-lunges');

  // Gaya renang change
  document.getElementById('renang-gaya')?.addEventListener('change', refreshAllBadges);

  // Live badge on input -- invalidasi cache PDF jika user ubah angka
  ['lari','pullup','situp','pushup','shuttle','lunges','renang'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
      refreshAllBadges();
      invalidateCachedPdf();
    });
  });

  // Nama peserta -- invalidasi cache (nama muncul di PDF)
  document.getElementById('nama')?.addEventListener('input', invalidateCachedPdf);
});
