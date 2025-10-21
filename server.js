import express from "express";
import dotenv from "dotenv";
import PDFDocument from "pdfkit";
import { google } from "googleapis";

dotenv.config();
const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static("public"));

const {
  GMAIL_USER,        // np. testemerlog2@gmail.com
  GCLIENT_ID,        // OAuth Client ID
  GCLIENT_SECRET,    // OAuth Client Secret
  GREFRESH_TOKEN     // OAuth Refresh Token
} = process.env;

if (!GMAIL_USER || !GCLIENT_ID || !GCLIENT_SECRET || !GREFRESH_TOKEN) {
  console.warn("Brak wymaganych env: GMAIL_USER, GCLIENT_ID, GCLIENT_SECRET, GREFRESH_TOKEN");
}

function gmailClient() {
  const oAuth2 = new google.auth.OAuth2(GCLIENT_ID, GCLIENT_SECRET);
  oAuth2.setCredentials({ refresh_token: GREFRESH_TOKEN });
  return google.gmail({ version: "v1", auth: oAuth2 });
}

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

function base64url(buf) {
  return Buffer.from(buf).toString("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

function buildRawEmail({ from, to, subject, text, pdf }) {
  const boundary = "mixed-boundary";
  const headers = [
    `From: ${from}`,
    `To: ${to}`,
    `Subject: ${subject}`,
    `MIME-Version: 1.0`,
    `Content-Type: multipart/mixed; boundary="${boundary}"`
  ].join("\r\n");

  const textPart =
`--${boundary}
Content-Type: text/plain; charset="utf-8"
Content-Transfer-Encoding: 7bit

${text || "W załączniku PDF."}
`;

  const attachmentPart =
`--${boundary}
Content-Type: application/pdf; name="raport-godzin.pdf"
Content-Disposition: attachment; filename="raport-godzin.pdf"
Content-Transfer-Encoding: base64

${Buffer.from(pdf).toString("base64").replace(/(.{76})/g, "$1\r\n")}
`;

  const ending = `--${boundary}--`;

  const raw = `${headers}\r\n\r\n${textPart}\r\n${attachmentPart}\r\n${ending}`;
  return base64url(raw);
}

app.post("/send", async (req, res) => {
  try {
    const { to, subject, message } = req.body || {};
    if (!to) return res.status(400).json({ ok: false, error: "Brak pola 'to'." });

    const pdf = await genPdfBuffer({
      title: subject || "Test PDF",
      body: message || "Treść testowa PDF."
    });

    const gmail = gmailClient();
    const raw = buildRawEmail({
      from: `Emerlog Test <${GMAIL_USER}>`,
      to,
      subject: subject || "Test wysyłki PDF",
      text: message || "W załączniku PDF.",
      pdf
    });

    const r = await gmail.users.messages.send({
      userId: "me",
      requestBody: { raw }
    });

    return res.json({ ok: true, id: r.data.id || null });
  } catch (e) {
    console.error(e);
    return res.status(500).json({ ok: false, error: String(e.message || e) });
  }
});

app.get("/healthz", (_req, res) => res.type("text/plain").send("ok"));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Listening on ${PORT}`));
