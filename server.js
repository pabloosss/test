// server.js — wersja pod Brevo API (HTTPS, działa na Render Free)
import express from "express";
import dotenv from "dotenv";
import PDFDocument from "pdfkit";

dotenv.config();
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static("public"));

const { BREVO_API_KEY, MAIL_FROM } = process.env;

function genPdfBuffer({ title, body }) {
  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({ size: "A4", margin: 40 });
    const chunks = [];
    doc.on("data", c => chunks.push(c));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    doc.fontSize(18).text(title || "Raport godzin – test", { underline: true });
    doc.moveDown();
    doc.fontSize(12).text(body || "To jest plik testowy PDF.");
    doc.end();
  });
}

function parseSender(mailFrom) {
  // akceptuje "Nazwa <email@domena>" lub sam email
  const m = /<(.*?)>/.exec(mailFrom || "");
  const email = m ? m[1] : (mailFrom || "no-reply@example.com");
  const n = /^(.*?)</.exec(mailFrom || "");
  const name = n ? n[1].trim() : "Mailer";
  return { name, email };
}

app.post("/send", async (req, res) => {
  try {
    if (!BREVO_API_KEY) return res.status(500).json({ ok: false, error: "Brak BREVO_API_KEY" });
    if (!MAIL_FROM) return res.status(500).json({ ok: false, error: "Brak MAIL_FROM" });

    const { to, subject, message } = req.body || {};
    if (!to) return res.status(400).json({ ok: false, error: "Brak pola 'to'" });

    const pdf = await genPdfBuffer({
      title: subject || "Test PDF",
      body: message || "Treść testowa PDF."
    });

    const sender = parseSender(MAIL_FROM);

    const r = await fetch("https://api.brevo.com/v3/smtp/email", {
      method: "POST",
      headers: {
        accept: "application/json",
        "api-key": BREVO_API_KEY,
        "content-type": "application/json"
      },
      body: JSON.stringify({
        sender,                          // { name, email } — email musi być zweryfikowany w Brevo
        to: [{ email: to }],
        subject: subject || "Test wysyłki PDF",
        textContent: message || "W załączniku PDF.",
        attachment: [
          { name: "raport-godzin.pdf", content: pdf.toString("base64") }
        ]
      })
    });

    const data = await r.json().catch(() => ({}));
    if (!r.ok) return res.status(502).json({ ok: false, error: data?.message || JSON.stringify(data) });

    return res.json({ ok: true, response: data });
  } catch (e) {
    return res.status(500).json({ ok: false, error: String(e.message || e) });
  }
});

app.get("/healthz", (_req, res) => res.type("text/plain").send("ok"));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Listening on ${PORT}`));
