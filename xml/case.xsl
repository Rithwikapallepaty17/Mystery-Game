<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" indent="yes" encoding="UTF-8"/>

<xsl:template match="/casefile">
<html>
<head>
<title><xsl:value-of select="title"/></title>
<style>
  body { font-family: Georgia, serif; background: #f3efe4; color: #2b2b2b; margin: 30px; }
  h1 { color: #7a4b00; border-bottom: 3px solid #7a4b00; padding-bottom: 6px; }
  h2 { color: #7a4b00; margin-top: 30px; }
  table { border-collapse: collapse; width: 100%; background: #fffdf7; margin-top: 10px; }
  th, td { border: 1px solid #bdb59f; padding: 8px; text-align: left; vertical-align: top; }
  th { background-color: #9e9e9e; color: #000; font-weight: bold; text-transform: uppercase; }
  .meta th { width: 160px; }
  .solution { background: #fdecea; border: 2px solid #c0392b; padding: 12px; margin-top: 10px; }
</style>
</head>
<body>
  <h1><xsl:value-of select="title"/> <small>(<xsl:value-of select="@difficulty"/>)</small></h1>

  <table class="meta">
    <tr><th>Victim</th><td><xsl:value-of select="victim"/></td></tr>
    <tr><th>Location</th><td><xsl:value-of select="location"/></td></tr>
    <tr><th>Time</th><td><xsl:value-of select="incident_time"/></td></tr>
  </table>
  <p><xsl:value-of select="summary"/></p>

  <h2>Suspects</h2>
  <table>
    <tr><th>Name</th><th>Occupation</th><th>Statement</th><th>Alibi</th><th>Motive</th></tr>
    <xsl:for-each select="suspects/suspect">
      <tr>
        <td><xsl:value-of select="name"/></td>
        <td><xsl:value-of select="occupation"/></td>
        <td><xsl:value-of select="statement"/></td>
        <td><xsl:value-of select="alibi"/></td>
        <td><xsl:value-of select="motive"/></td>
      </tr>
    </xsl:for-each>
  </table>

  <h2>Evidence</h2>
  <table>
    <tr><th>Evidence</th><th>Details</th><th>Found at</th><th>Cost</th></tr>
    <xsl:for-each select="evidence_list/evidence">
      <tr>
        <td><xsl:value-of select="title"/></td>
        <td><xsl:value-of select="description"/></td>
        <td><xsl:value-of select="found_at"/></td>
        <td><xsl:value-of select="@cost"/></td>
      </tr>
    </xsl:for-each>
  </table>

  <xsl:if test="solution">
    <h2>Solution</h2>
    <div class="solution">
      <strong>Culprit: <xsl:value-of select="solution/culprit"/></strong>
      <p><xsl:value-of select="solution/explanation"/></p>
    </div>
  </xsl:if>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
