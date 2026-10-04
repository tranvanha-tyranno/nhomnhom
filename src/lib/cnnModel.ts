import * as tf from "@tensorflow/tfjs";

export type ImageShape = readonly [height: number, width: number, channels: number];

export type CnnModelOptions = {
  classCount: number;
  inputShape?: ImageShape;
};

const defaultInputShape: ImageShape = [64, 64, 3];

export function createCnnModel({
  classCount,
  inputShape = defaultInputShape
}: CnnModelOptions): tf.Sequential {
  validateClassCount(classCount);
  validateInputShape(inputShape);

  const model = tf.sequential({
    layers: [
      tf.layers.conv2d({
        inputShape: [...inputShape],
        filters: 16,
        kernelSize: 3,
        activation: "relu",
        padding: "same"
      }),
      tf.layers.maxPooling2d({ poolSize: 2 }),
      tf.layers.conv2d({
        filters: 32,
        kernelSize: 3,
        activation: "relu",
        padding: "same"
      }),
      tf.layers.maxPooling2d({ poolSize: 2 }),
      tf.layers.flatten(),
      tf.layers.dense({ units: 64, activation: "relu" }),
      tf.layers.dense({ units: classCount, activation: "softmax" })
    ]
  });

  model.compile({
    optimizer: tf.train.adam(),
    loss: "categoricalCrossentropy",
    metrics: ["accuracy"]
  });

  return model;
}

function validateClassCount(classCount: number): void {
  if (!Number.isInteger(classCount) || classCount <= 1) {
    throw new RangeError("classCount must be an integer greater than 1.");
  }
}

function validateInputShape(inputShape: ImageShape): void {
  if (
    inputShape.length !== 3 ||
    !inputShape.every((dimension) => Number.isInteger(dimension) && dimension > 0)
  ) {
    throw new RangeError("inputShape dimensions must be positive integers.");
  }

  if (inputShape[0] < 4 || inputShape[1] < 4) {
    throw new RangeError("inputShape height and width must be at least 4 pixels.");
  }
}
