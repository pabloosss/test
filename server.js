const express = require("express");
const path = require("path");
const cors = require("cors");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3000;

// fetch fallback
const fetchFn =
  (typeof fetch !== "undefined" && fetch) ||
  ((...args) => import("node-fetch").then(m => m.default(...args)));

const MAIL_TO = process.env.MAIL_TO || "ewa.dusinska@emerlog.eu";
const MAIL_FROM = process.env.MAIL_FROM || "Emerlog <no-reply@emerlog.eu>";
const BREVO_API_KEY = process.env.BREVO_API_KEY || process.env.BREVO_API || "";

function parseFrom(raw){
  const m=/^(.*)<([^>]+)>$/.exec(raw);
  return m ? { name:m[1].trim(), email:m[2].trim() } : { name:raw, email:raw };
}
const SENDER = parseFrom(MAIL_FROM);

app.use(cors());
app.use(express.json({ limit:"100mb" }));
app.use(express.static(path.join(__dirname, "public")));

app.get("/test", (_req,res)=>res.json({ ok:true }));

app.post("/send-pdf", async (req,res)=>{
  try{
    const { name, pdfData } = req.body || {};
    if(!name || !pdfData) return res.status(400).json({ ok:false, error:"BRK_DANYCH" });
    if(!BREVO_API_KEY)   return res.status(500).json({ ok:false, error:"BRK_BREVO_API_KEY" });

    const payload = {
      sender: { name:SENDER.name, email:SENDER.email },
      to: [{ email: MAIL_TO, name: "Paweł Ruchlicki" }],
      subject: `Rozliczenie godzin – ${name}`,
      htmlContent: `<p>Dzień dobry,<br> w załączniku rozliczenie godzin dla <b>${name}</b>.</p><p>Pozdrawiamy,<br>Emerlog</p>`,
      attachment: [{ name:"Tabela_Godzinowa.pdf", content: pdfData }]
    };

    const resp = await fetchFn("https://api.brevo.com/v3/smtp/email", {
      method:"POST",
      headers:{ accept:"application/json","content-type":"application/json","api-key":BREVO_API_KEY },
      body: JSON.stringify(payload)
    });

    const data = await resp.json().catch(()=>({}));
    if(!resp.ok) return res.status(502).json({ ok:false, error:data?.message || "BREVO_FAILED" });
    res.json({ ok:true, messageId:data?.messageId || null });
  }catch(e){
    console.error("send-pdf error:", e);
    res.status(500).json({ ok:false, error:"SERVER_ERROR" });
  }
});

app.listen(PORT, ()=>console.log(`Serwer: ${PORT}`));
