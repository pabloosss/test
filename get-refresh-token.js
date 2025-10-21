import express from "express";
import { google } from "googleapis";
import open from "open";

const CLIENT_ID = process.env.GCLIENT_ID;
const CLIENT_SECRET = process.env.GCLIENT_SECRET;
const REDIRECT_URI = "http://localhost:3000/oauth2callback"; // dodany w GCP

if (!CLIENT_ID || !CLIENT_SECRET) {
  console.error("Ustaw GCLIENT_ID i GCLIENT_SECRET w env.");
  process.exit(1);
}

const app = express();
const oauth2Client = new google.auth.OAuth2(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

const scopes = ["https://www.googleapis.com/auth/gmail.send"];

app.get("/start", (_req, res) => {
  const url = oauth2Client.generateAuthUrl({ access_type: "offline", prompt: "consent", scope: scopes });
  res.send(`Kliknij: <a href="${url}">Autoryzuj Gmail</a>`);
});

app.get("/oauth2callback", async (req, res) => {
  try {
    const { code } = req.query;
    const { tokens } = await oauth2Client.getToken(code);
    res.send(`<pre>${JSON.stringify(tokens, null, 2)}</pre><p>Skopiuj refresh_token.</p>`);
    console.log("\n== TOKENS ==\n", tokens, "\n");
    console.log("REFRESH TOKEN:", tokens.refresh_token);
    process.exit(0);
  } catch (e) {
    console.error(e);
    res.status(500).send(String(e));
  }
});

app.listen(3000, async () => {
  const url = "http://localhost:3000/start";
  console.log("Uruchomiono. Otwieram:", url);
  await open(url);
});
