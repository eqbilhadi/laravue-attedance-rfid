// src/services/mqttService.ts
import mqtt, { MqttClient, IClientOptions } from "mqtt";

let client: MqttClient | null = null;

export function connectMqtt(onMessage?: (topic: string, payload: Buffer) => void) {
  if (client && client.connected) {
    return client; // pakai koneksi lama
  }

  const brokerUrl = import.meta.env.VITE_MQTT_BROKER_URL as string;
  const options: IClientOptions = {
    username: import.meta.env.VITE_MQTT_USERNAME,
    password: import.meta.env.VITE_MQTT_PASSWORD,
    protocol: "wss",
    clientId: "vue-app-" + Math.random().toString(16).substr(2, 8),
  };

  client = mqtt.connect(brokerUrl, options);

  client.on("connect", () => {
    console.log("✅ MQTT Connected");
  });

  client.on("error", (err) => {
    console.error("❌ MQTT Error:", err.message);
  });

  client.on("close", () => {
    console.warn("⚠️ MQTT Connection closed");
  });

  if (onMessage) {
    client.on("message", onMessage);
  }

  return client;
}

export function disconnectMqtt() {
  if (client) {
    client.end();
    client = null;
    console.log("🔌 MQTT Disconnected");
  }
}

export function publish(topic: string, message: string) {
  client?.publish(topic, message);
}

export function subscribe(topic: string) {
  client?.subscribe(topic);
}

export function unsubscribe(topic: string) {
  client?.unsubscribe(topic);
}
