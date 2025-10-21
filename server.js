import express from "express";
import nodemailer from "nodemailer";
import dotenv from "dotenv";
import PDFDocument from "pdfkit";

dotenv.config();

const app = express();
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static("public"));

const {
  SMTP_HOST,
  SMTP_PORT,
  SMTP_SECURE,     // "true" albo "false"
  SMTP_USER,
  SMTP_PASS,
  MAIL_FROM        // np. "IT <it@twojadomena.pl>"
} = process.env;

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

function buildTransporter() {
  return nodemailer.createTransport({
    host: SMTP_HOST,
    port: Number(SMTP_PORT || 587),
    secure: String(SMTP_SECURE || "false") === "true",
    auth: {
      user: SMTP_USER,
      pass: SMTP_PASS
    }
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

    const transporter = buildTransporter();

    const info = await transporter.sendMail({
      from: MAIL_FROM || SMTP_USER,
      to,
      subject: subject || "Test wysyłki PDF",
      text: message || "W załączniku PDF.",
      attachments: [
        {
          filename: "raport-godzin.pdf",
          content: pdf,
          contentType: "application/pdf"
        }
      ]
    });

    res.json({ ok: true, messageId: info.messageId });
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
