// server.js
import express from "express";
import dotenv from "dotenv";
import PDFDocument from "pdfkit";
import { Resend } from "resend";

dotenv.config();

const app = express();
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static("public"));

const {
  RESEND_API_KEY,
  MAIL_FROM, // np. "Emerlog Test <onboarding@resend.dev>" albo zweryfikowana domena
} = process.env;

if (!RESEND_API_KEY) {
  console.warn("Brak RESEND_API_KEY w env. Wysyłka nie zadziała.");
}

const resend = new Resend(RESEND_API_KEY);

function genPdfBuffer({ title, body }) {
  return new Promise((resolve, reject) => {
    const doc = new PDFDocument({ size: "A4", margin: 40 });
    const chunks = [];
    doc.on("data", (c) => chunks.push(c));
    doc.on("end", () => resolve(Buffer.concat(chunks)));
    doc.on("error", reject);

    doc.fontSize(18).text(title || "Raport godzin – test", { underline: true });
    doc.moveDown();
    doc.fontSize(12).text(body || "To jest plik testowy PDF z serwera Render.");
    doc.end();
  });
}

app.post("/send", async (req, res) => {
  try {
    const { to, subject, message } = req.body;
    if (!to) return res.status(400).json({ ok: false, error: "Brak pola 'to'." });

    const pdf = await genPdfBuffer({
      title: subject || "Test PDF",
      body: message || "Treść testowa PDF."
    });

    const result = await resend.emails.send({
      from: MAIL_FROM || "Emerlog Test <onboarding@resend.dev>",
      to,
      subject: subject || "Test wysyłki PDF",
      html: `<p>${(message || "W załączniku PDF.").replace(/</g, "&lt;")}</p>`,
      attachments: [
        {
          filename: "raport-godzin.pdf",
          content: pdf.toString("base64")
        }
      ]
    });

    if (result.error) {
      return res.status(500).json({ ok: false, error: result.error.message || "Resend error" });
    }

    res.json({ ok: true, id: result.data?.id || null });
  } catch (e) {
    console.error(e);
    res.status(500).json({ ok: false, error: String(e.message || e) });
  }
});

app.get("/healthz", (_req, res) => res.send("ok"));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Listening on ${PORT}`);
});
