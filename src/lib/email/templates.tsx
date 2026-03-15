import { Body, Container, Head, Heading, Html, Preview, Section, Text } from "@react-email/components";
import * as React from "react";

type TemplateProps = {
  heading: string;
  body: string;
};

export function TournamentEmailTemplate({ heading, body }: TemplateProps) {
  return (
    <Html>
      <Head />
      <Preview>{heading}</Preview>
      <Body style={{ backgroundColor: "#f4f1ea", fontFamily: "Arial, sans-serif", padding: "24px" }}>
        <Container style={{ backgroundColor: "#ffffff", borderRadius: "20px", padding: "32px" }}>
          <Section>
            <Text style={{ color: "#855f38", letterSpacing: "0.2em", textTransform: "uppercase" }}>
              Spring Shootout
            </Text>
            <Heading style={{ color: "#14213d", fontSize: "28px", marginBottom: "16px" }}>
              {heading}
            </Heading>
            <Text style={{ color: "#3d405b", fontSize: "16px", lineHeight: "1.7" }}>{body}</Text>
          </Section>
        </Container>
      </Body>
    </Html>
  );
}
